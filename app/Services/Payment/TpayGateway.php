<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Events\OrderCreated;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tpay\OpenApi\Api\TpayApi;
use Tpay\OpenApi\Utilities\Cache;

/**
 * Tpay Gateway – uses official tpay-com/tpay-openapi-php SDK.
 */
class TpayGateway implements PaymentGatewayInterface
{
    protected bool $configured = false;
    protected ?TpayApi $api = null;

    public function __construct(?TpayApi $api = null)
    {
        if ($api !== null) {
            // Injected (e.g. from tests)
            $this->api        = $api;
            $this->configured = true;
            return;
        }

        $clientId     = Setting::get('tpay_client_id', '');
        $clientSecret = Setting::get('tpay_client_secret', '');
        $mode         = Setting::get('tpay_mode', 'sandbox');

        if (!empty($clientId) && !empty($clientSecret)) {
            $laravelCache = app('cache')->driver();
            $tpayCache    = new Cache(null, $laravelCache);

            $this->api        = new TpayApi($tpayCache, $clientId, $clientSecret, $mode === 'production');
            $this->configured = true;
        }
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function getName(): string
    {
        return 'Tpay';
    }

    public function createPayment(Order $order): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Tpay nie jest skonfigurowane dla tej restauracji.'];
        }

        $amount = round($order->total, 2);

        $fields = [
            'amount'      => $amount,
            'description' => "Zamówienie #{$order->order_number}",
            'payer'       => [
                'email' => $order->customer_email ?: 'noreply@example.com',
                'name'  => $order->customer_name ?: 'Klient',
                'phone' => $order->customer_phone ?: '',
            ],
            'callbacks'   => [
                'payerUrls' => [
                    'success' => route('tenant.payment.return', $order),
                    'error'   => route('tenant.payment.return', $order) . '?error=1',
                ],
                'notification' => [
                    'url'   => route('tenant.payment.webhook.tpay'),
                    'email' => Setting::get('restaurant_email', ''),
                ],
            ],
        ];

        try {
            $result = $this->api->transactions()->createTransaction($fields);

            $transactionId  = $result->transactionId ?? null;
            $transactionUrl = $result->transactionPaymentUrl ?? null;

            if ($transactionId && $transactionUrl) {
                $order->update(['payment_data' => ['tpay_transaction_id' => $transactionId, 'ext_order_id' => $order->order_number]]);
                return ['success' => true, 'payment_url' => $transactionUrl];
            }

            Log::error('Tpay createTransaction Failed', ['result' => (array) $result]);
            return ['success' => false, 'error' => 'Błąd tworzenia płatności Tpay'];

        } catch (\Exception $e) {
            Log::error('Tpay Exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function handleReturn(Request $request, Order $order): bool
    {
        if ($request->get('error')) {
            return false;
        }

        // Check via API
        $order->refresh();
        if ($order->payment_status === 'paid') {
            return true;
        }

        // Optionally poll API for status
        $paymentData   = $order->payment_data ?? [];
        $transactionId = $paymentData['tpay_transaction_id'] ?? null;

        if ($transactionId && $this->api) {
            try {
                $txn    = $this->api->transactions()->getTransactionById($transactionId);
                $status = $txn->status ?? null;

                if ($status === 'correct') {
                    $this->markPaid($order, $paymentData, $transactionId);
                    return true;
                }
            } catch (\Exception $e) {
                Log::error('Tpay handleReturn Exception', ['message' => $e->getMessage()]);
            }
        }

        return false;
    }

    public function handleWebhook(array $data): bool
    {
        try {
            $transactionId = $data['id'] ?? null;
            $status        = $data['status'] ?? null;
            $extOrderId    = null;

            // Find by tpay_transaction_id in payment_data
            $order = Order::whereJsonContains('payment_data->tpay_transaction_id', $transactionId)->first();

            if (!$order && isset($data['tr_id'])) {
                $order = Order::whereJsonContains('payment_data->tpay_transaction_id', $data['tr_id'])->first();
            }

            if (!$order) {
                Log::error('Tpay Webhook: Order not found', ['transaction_id' => $transactionId]);
                return false;
            }

            if (in_array($status, ['correct', 'paid'])) {
                $this->markPaid($order, $order->payment_data ?? [], $transactionId);
                return true;
            }

            if ($status === 'err') {
                $order->update(['payment_status' => 'failed']);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Tpay Webhook Exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    private function markPaid(Order $order, array $paymentData, ?string $transactionId): void
    {
        $order->update([
            'payment_status' => 'paid',
            'paid_at'        => now(),
            'status'         => 'confirmed',
            'payment_data'   => array_merge($paymentData, ['tpay_transaction_id' => $transactionId, 'verified_at' => now()->toDateTimeString()]),
        ]);
        Log::info('Tpay: płatność potwierdzona', ['order_number' => $order->order_number, 'total' => $order->total, 'transaction_id' => $transactionId]);
        event(new OrderCreated($order->fresh()));
    }
}
