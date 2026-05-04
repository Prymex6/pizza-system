<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Events\OrderCreated;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;

/**
 * Stripe Gateway – uses official stripe/stripe-php SDK via injectable StripeClient.
 */
class StripeGateway implements PaymentGatewayInterface
{
    protected bool $configured = false;
    protected string $webhookSecret = '';

    /** @var StripeClient|object|null */
    protected $client = null;

    public function __construct($client = null)
    {
        $this->webhookSecret = (string) Setting::get('stripe_webhook_secret', '');

        if ($client !== null) {
            // Injected (e.g. from tests)
            $this->client    = $client;
            $this->configured = true;
            return;
        }

        $secretKey = Setting::get('stripe_secret_key', '');

        if (!empty($secretKey)) {
            $this->client    = new StripeClient($secretKey);
            $this->configured = true;
        }
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function getName(): string
    {
        return 'Stripe';
    }

    public function createPayment(Order $order): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Stripe nie jest skonfigurowany dla tej restauracji.'];
        }

        $amount = (int) round($order->total * 100); // grosze

        try {
            $session = $this->client->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => 'pln',
                        'unit_amount'  => $amount,
                        'product_data' => [
                            'name' => "Zamówienie #{$order->order_number}",
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode'          => 'payment',
                'customer_email' => $order->customer_email ?: null,
                'success_url'   => route('tenant.payment.return', $order) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'    => route('tenant.payment.return', $order) . '?canceled=1',
                'metadata'      => [
                    'order_number' => $order->order_number,
                    'order_id'     => $order->id,
                ],
            ]);

            $order->update(['payment_data' => [
                'stripe_session_id'     => $session->id,
                'stripe_payment_intent' => $session->payment_intent,
            ]]);

            return ['success' => true, 'payment_url' => $session->url];

        } catch (ApiErrorException $e) {
            Log::error('Stripe Exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function handleReturn(Request $request, Order $order): bool
    {
        if ($request->get('canceled')) {
            return false;
        }

        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            $order->refresh();
            return $order->payment_status === 'paid';
        }

        try {
            $session = $this->client->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $paymentData = $order->payment_data ?? [];
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at'        => now(),
                    'status'         => 'confirmed',
                    'payment_data'   => array_merge($paymentData, [
                        'stripe_session_id' => $sessionId,
                        'verified_at'       => now()->toDateTimeString(),
                    ]),
                ]);
                Log::info('Stripe: płatność potwierdzona (handleReturn)', ['order_number' => $order->order_number, 'total' => $order->total]);
                event(new OrderCreated($order->fresh()));
                return true;
            }
        } catch (ApiErrorException $e) {
            Log::error('Stripe handleReturn Exception', ['message' => $e->getMessage()]);
        }

        return false;
    }

    public function handleWebhook(array $data): bool
    {
        $type  = $data['type'] ?? null;
        $event = $data['data']['object'] ?? null;

        if ($type === 'checkout.session.completed' && isset($event['metadata']['order_number'])) {
            $orderNumber = $event['metadata']['order_number'];
            $order       = Order::where('order_number', $orderNumber)->first();

            if ($order && $event['payment_status'] === 'paid') {
                $paymentData = $order->payment_data ?? [];
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at'        => now(),
                    'status'         => 'confirmed',
                    'payment_data'   => array_merge($paymentData, [
                        'stripe_event_type' => $type,
                        'verified_at'       => now()->toDateTimeString(),
                    ]),
                ]);
                Log::info('Stripe: płatność potwierdzona (webhook)', ['order_number' => $order->order_number, 'total' => $order->total]);
                event(new OrderCreated($order->fresh()));
            }
        }

        return true;
    }

    public function refund(Order $order): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Stripe nie jest skonfigurowane.'];
        }

        $paymentData    = $order->payment_data ?? [];
        $paymentIntent  = $paymentData['stripe_payment_intent'] ?? null;

        if (!$paymentIntent) {
            return ['success' => false, 'error' => 'Brak payment_intent Stripe.'];
        }

        try {
            $this->client->refunds->create(['payment_intent' => $paymentIntent]);
            Log::info('Stripe refund success', ['order' => $order->order_number]);
            return ['success' => true];
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify and construct Stripe webhook event from raw payload.
     * Returns decoded event array or null if invalid signature.
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): ?array
    {
        if (empty($this->webhookSecret)) {
            return json_decode($payload, true);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
            return $event->toArray();
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook Signature Invalid', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
