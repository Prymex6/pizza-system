<?php

namespace App\Services;

use App\Models\Tenant\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $token;
    protected bool $enabled;

    public function __construct()
    {
        try {
            $this->token   = (string) Setting::get('smsapi_token', '');
            $this->enabled = (bool) Setting::get('sms_enabled', false) && !empty($this->token);
        } catch (\Exception $e) {
            $this->token   = '';
            $this->enabled = false;
        }
    }

    public function send(string $phone, string $message): bool
    {
        if (!$this->enabled) return false;

        $phone = $this->normalizePhone($phone);
        if (!$phone) return false;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->asForm()->post('https://api.smsapi.pl/sms.do', [
                'to'      => $phone,
                'message' => $message,
                'format'  => 'json',
            ]);

            if ($response->successful()) {
                Log::info('SMS wysłany', ['phone' => $phone]);
                return true;
            }

            Log::warning('SMS nieudany', ['phone' => $phone, 'status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Błąd SMS: ' . $e->getMessage());
            return false;
        }
    }

    public function sendOrderStatus(string $phone, string $orderNumber, string $status): void
    {
        $restaurantName = Setting::get('restaurant_name', 'Restauracja');

        $messages = [
            'paid'        => "{$restaurantName}: Zamówienie #{$orderNumber} zostało przyjęte! Przygotowujemy je dla Ciebie.",
            'preparing'   => "{$restaurantName}: Zamówienie #{$orderNumber} jest właśnie przygotowywane przez naszą kuchnię!",
            'ready'       => "{$restaurantName}: Zamówienie #{$orderNumber} gotowe! Wkrótce wyjeżdżamy do Ciebie.",
            'on_delivery' => "{$restaurantName}: Zamówienie #{$orderNumber} jest w drodze! Kierowca już jedzie.",
            'completed'   => "{$restaurantName}: Zamówienie #{$orderNumber} dostarczone. Smacznego! Dziękujemy.",
            'cancelled'   => "{$restaurantName}: Zamówienie #{$orderNumber} zostało anulowane. Przepraszamy za niedogodności.",
        ];

        if (isset($messages[$status])) {
            $this->send($phone, $messages[$status]);
        }
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $phone = '+48' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '+')) {
            $phone = '+48' . $phone;
        }

        return strlen($phone) >= 10 ? $phone : '';
    }
}
