<?php

namespace App\Services\Payment;

use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\OrderCreated;

class Przelewy24Service
{
    protected string $apiUrl;
    protected int $merchantId;
    protected int $posId;
    protected string $apiKey;
    protected string $crc;
    protected bool $isConfigured = false;
    protected bool $usePlatformCredentials = false;

    public function __construct(array $customCredentials = null)
    {
        if ($customCredentials) {
            $this->loadCustomConfiguration($customCredentials);
        } else {
            $this->loadTenantConfiguration();
        }
    }

    /**
     * Create instance for specific tenant (for subscription payments from platform)
     */
    public static function forPlatform(): self
    {
        $credentials = [
            'merchant_id' => config('przelewy24.platform_merchant_id'),
            'pos_id' => config('przelewy24.platform_pos_id'),
            'api_key' => config('przelewy24.platform_api_key'),
            'crc' => config('przelewy24.platform_crc'),
            'mode' => config('przelewy24.platform_mode', 'sandbox'),
        ];

        $instance = new self($credentials);
        $instance->usePlatformCredentials = true;
        return $instance;
    }

    /**
     * Create instance for current tenant (for customer payments)
     */
    public static function forTenant(): self
    {
        return new self();
    }

    /**
     * Load custom P24 configuration
     */
    protected function loadCustomConfiguration(array $credentials): void
    {
        $this->merchantId = (int) ($credentials['merchant_id'] ?? 0);
        $this->posId = (int) ($credentials['pos_id'] ?? $this->merchantId);
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->crc = $credentials['crc'] ?? '';

        $mode = $credentials['mode'] ?? 'sandbox';
        $this->apiUrl = $mode === 'production'
            ? config('przelewy24.urls.production')
            : config('przelewy24.urls.sandbox');

        $this->isConfigured = !empty($this->merchantId)
            && !empty($this->apiKey)
            && !empty($this->crc);
    }

    /**
     * Load Przelewy24 configuration from tenant settings
     */
    protected function loadTenantConfiguration(): void
    {
        // Load tenant-specific P24 credentials from tenant settings table
        $this->merchantId = (int) Setting::get('p24_merchant_id', 0);
        $this->posId = (int) Setting::get('p24_pos_id', $this->merchantId);
        $this->apiKey = Setting::get('p24_api_key', '');
        $this->crc = Setting::get('p24_crc', '');

        // Determine API URL based on mode
        $mode = Setting::get('p24_mode', 'sandbox');
        $this->apiUrl = $mode === 'production'
            ? config('przelewy24.urls.production')
            : config('przelewy24.urls.sandbox');

        // Check if service is properly configured
        $this->isConfigured = !empty($this->merchantId)
            && !empty($this->apiKey)
            && !empty($this->crc);
    }

    /**
     * Check if Przelewy24 is configured for this tenant
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Zarejestruj transakcję w P24 i otrzymaj token
     */
    public function registerTransaction(Order $order): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Przelewy24 is not configured for this restaurant. Please contact the restaurant owner.',
            ];
        }

        $amount = (int) round($order->total * 100); // Kwota w groszach

        $params = [
            'merchantId' => $this->merchantId,
            'posId' => $this->posId,
            'sessionId' => $this->generateSessionId($order),
            'amount' => $amount,
            'currency' => config('przelewy24.currency'),
            'description' => "Zamówienie #{$order->order_number}",
            'email' => $order->customer_email ?: 'noreply@example.com',
            'client' => $order->customer_name,
            'country' => 'PL',
            'language' => config('przelewy24.language'),
            'urlReturn' => route('tenant.payment.return', $order),
            'urlStatus' => route('tenant.payment.webhook'),
            'encoding' => 'UTF-8',
        ];

        // Oblicz znak (signature)
        $params['sign'] = $this->calculateSign([
            'sessionId' => $params['sessionId'],
            'merchantId' => $this->merchantId,
            'amount' => $amount,
            'currency' => $params['currency'],
            'crc' => $this->crc,
        ]);

        try {
            $response = Http::withBasicAuth($this->posId, $this->apiKey)
                ->post("{$this->apiUrl}/api/v1/transaction/register", $params);

            $data = $response->json();

            if (isset($data['data']['token'])) {
                // Zapisz token w bazie
                $order->update([
                    'payment_data' => [
                        'p24_token' => $data['data']['token'],
                        'p24_session_id' => $params['sessionId'],
                    ],
                ]);

                return [
                    'success' => true,
                    'token' => $data['data']['token'],
                    'payment_url' => "{$this->apiUrl}/trnRequest/{$data['data']['token']}",
                ];
            }

            Log::error('P24 Registration Failed', ['response' => $data]);

            return [
                'success' => false,
                'error' => $data['error'] ?? 'Nieznany błąd',
            ];

        } catch (\Exception $e) {
            Log::error('P24 Registration Exception', [
                'message' => $e->getMessage(),
                'order_id' => $order->id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Weryfikuj płatność po powrocie z P24
     */
    public function verifyTransaction(Order $order, string $orderId, int $amount): bool
    {
        $paymentData = $order->payment_data ?? [];
        $sessionId = $paymentData['p24_session_id'] ?? null;

        if (!$sessionId) {
            Log::error('P24 Verification: Missing session ID', ['order_id' => $order->id]);
            return false;
        }

        $params = [
            'merchantId' => $this->merchantId,
            'posId' => $this->posId,
            'sessionId' => $sessionId,
            'amount' => $amount,
            'currency' => config('przelewy24.currency'),
            'orderId' => (int) $orderId,
        ];

        // Oblicz sign dla weryfikacji
        $params['sign'] = $this->calculateSign([
            'sessionId' => $sessionId,
            'orderId' => (int) $orderId,
            'amount' => $amount,
            'currency' => $params['currency'],
            'crc' => $this->crc,
        ]);

        try {
            $response = Http::withBasicAuth($this->posId, $this->apiKey)
                ->put("{$this->apiUrl}/api/v1/transaction/verify", $params);

            $data = $response->json();

            if (isset($data['data']['status']) && $data['data']['status'] === 'success') {
                // Płatność zweryfikowana - zaktualizuj zamówienie
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'status' => 'paid',
                    'payment_data' => array_merge($paymentData, [
                        'p24_order_id' => $orderId,
                        'verified_at' => now()->toDateTimeString(),
                    ]),
                ]);

                Log::info('P24 Payment Verified', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);

                // Broadcast order created event after successful payment
                event(new OrderCreated($order));

                return true;
            }

            Log::warning('P24 Verification Failed', [
                'order_id' => $order->id,
                'response' => $data,
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('P24 Verification Exception', [
                'message' => $e->getMessage(),
                'order_id' => $order->id,
            ]);

            return false;
        }
    }

    /**
     * Obsłuż webhook od P24
     */
    public function handleWebhook(array $data): bool
    {
        // Weryfikuj sign z webhooka
        $receivedSign = $data['sign'] ?? '';
        $calculatedSign = $this->calculateSign([
            'sessionId' => $data['sessionId'] ?? '',
            'orderId' => (int) ($data['orderId'] ?? 0),
            'amount' => (int) ($data['amount'] ?? 0),
            'currency' => $data['currency'] ?? 'PLN',
            'crc' => $this->crc,
        ]);

        if ($receivedSign !== $calculatedSign) {
            Log::error('P24 Webhook: Invalid signature', [
                'received' => $receivedSign,
                'calculated' => $calculatedSign,
            ]);
            return false;
        }

        // Znajdź zamówienie po session_id
        $sessionId = $data['sessionId'];
        $order = Order::whereJsonContains('payment_data->p24_session_id', $sessionId)->first();

        if (!$order) {
            Log::error('P24 Webhook: Order not found', ['session_id' => $sessionId]);
            return false;
        }

        // Zweryfikuj transakcję
        return $this->verifyTransaction(
            $order,
            (string) $data['orderId'],
            (int) $data['amount']
        );
    }

    /**
     * Generuj unikalny session ID dla zamówienia
     */
    protected function generateSessionId(Order $order): string
    {
        return $order->order_number . '_' . time();
    }

    /**
     * Oblicz sign (SHA384)
     */
    protected function calculateSign(array $params): string
    {
        $json = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash('sha384', $json);
    }

    /**
     * Testuj połączenie z P24 (tylko sandbox)
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Przelewy24 nie jest skonfigurowane dla tej restauracji.',
            ];
        }

        if (Setting::get('p24_mode', 'sandbox') !== 'sandbox') {
            return [
                'success' => false,
                'message' => 'Test połączenia jest dostępny tylko w trybie sandbox.',
            ];
        }

        try {
            $response = Http::withBasicAuth($this->posId, $this->apiKey)
                ->get("{$this->apiUrl}/api/v1/testAccess");

            $data = $response->json();

            return [
                'success' => isset($data['data']) && $data['data'] === true,
                'response' => $data,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
