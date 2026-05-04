<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Inicjalizuj płatność – wybiera gateway na podstawie payment_method zamówienia
     */
    public function initiate(Order $order)
    {
        if ($order->payment_status === 'paid') {
            return redirect()->route('tenant.menu')->with('info', 'To zamówienie zostało już opłacone.');
        }

        $method = $order->payment_method;

        if (in_array($method, ['cash', 'card_on_delivery'])) {
            return redirect()->route('tenant.menu')->with('error', 'To zamówienie nie wymaga płatności online.');
        }

        try {
            $gateway = PaymentGatewayFactory::make($method);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('tenant.menu')->with('error', 'Nieznana metoda płatności.');
        }

        if (!$gateway->isConfigured()) {
            return redirect()->route('tenant.menu')->with('error', "Bramka płatności {$gateway->getName()} nie jest skonfigurowana.");
        }

        $result = $gateway->createPayment($order);

        if ($result['success']) {
            return redirect($result['payment_url']);
        }

        Log::error('Payment initiation failed', ['method' => $method, 'error' => $result['error'] ?? '?']);
        return redirect()->route('tenant.menu')->with('error', 'Nie udało się zainicjować płatności: ' . ($result['error'] ?? ''));
    }

    /**
     * Powrót klienta z bramki płatności
     */
    public function return(Request $request, Order $order)
    {
        $method = $order->payment_method;

        try {
            $gateway = PaymentGatewayFactory::make($method);
        } catch (\InvalidArgumentException) {
            return $this->paymentResultPage(false, 'Nieznana metoda płatności.');
        }

        $success = $gateway->handleReturn($request, $order);

        if ($success) {
            return redirect()->route('tenant.order.tracking', $order->order_number);
        }

        return $this->paymentResultPage(false, 'Nie udało się zweryfikować płatności. Skontaktuj się z nami.');
    }

    /**
     * Webhook P24 (legacy + default)
     */
    public function webhook(Request $request)
    {
        Log::info('P24 Webhook Received', $request->all());
        $gateway = PaymentGatewayFactory::make('przelewy24');
        $success = $gateway->handleWebhook($request->all());
        return $success ? response('OK', 200) : response('ERROR', 400);
    }

    /**
     * Webhook PayU (IPN notification)
     */
    public function webhookPayU(Request $request)
    {
        Log::info('PayU Webhook Received', $request->all());
        $gateway = PaymentGatewayFactory::make('payu');
        $success = $gateway->handleWebhook($request->all());
        return $success ? response()->json(['status' => 'OK']) : response()->json(['status' => 'ERROR'], 400);
    }

    /**
     * Webhook Tpay
     */
    public function webhookTpay(Request $request)
    {
        Log::info('Tpay Webhook Received', $request->all());
        $gateway = PaymentGatewayFactory::make('tpay');
        $success = $gateway->handleWebhook($request->all());
        return $success ? response()->json(['result' => '1']) : response()->json(['result' => '0'], 400);
    }

    /**
     * Webhook Stripe
     */
    public function webhookStripe(Request $request)
    {
        Log::info('Stripe Webhook Received');

        /** @var StripeGateway $gateway */
        $gateway = PaymentGatewayFactory::make('stripe');

        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');
        $data      = $gateway->constructWebhookEvent($payload, $sigHeader);

        if ($data === null) {
            return response()->json(['error' => 'Nieprawidłowa sygnatura'], 400);
        }

        $success = $gateway->handleWebhook($data);
        return $success ? response()->json(['received' => true]) : response()->json(['error' => 'Błąd przetwarzania'], 400);
    }

    private function paymentResultPage(bool $success, string $message)
    {
        return Inertia::render('Tenant/Client/PaymentResult', [
            'success' => $success,
            'message' => $message,
        ]);
    }
}
