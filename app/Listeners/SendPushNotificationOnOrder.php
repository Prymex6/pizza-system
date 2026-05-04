<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Models\Tenant\PushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPushNotificationOnOrder
{
    /**
     * Roles that should receive push for a given situation.
     *
     * OrderCreated  → manager (new order from customer)
     * status=paid   → kitchen (order accepted, start preparing)
     * status=ready  → driver  (delivery ready for pickup)
     * All statuses  → manager (always)
     */
    public function handle(OrderCreated|OrderStatusChanged $event): void
    {
        $vapidPublicKey  = config('webpush.vapid.public_key');
        $vapidPrivateKey = config('webpush.vapid.private_key');

        if (!$vapidPublicKey || !$vapidPrivateKey) {
            Log::warning('Web Push: VAPID keys not configured');
            return;
        }

        if ($event instanceof OrderCreated) {
            $this->handleOrderCreated($event, $vapidPublicKey, $vapidPrivateKey);
        } else {
            $this->handleStatusChanged($event, $vapidPublicKey, $vapidPrivateKey);
        }
    }

    private function handleOrderCreated(OrderCreated $event, string $pub, string $priv): void
    {
        $order = $event->order;
        $title = 'Nowe zamówienie #' . $order->order_number;
        $body  = ucfirst($order->type === 'delivery' ? 'dostawa' : ($order->type === 'pickup' ? 'odbiór osobisty' : 'na miejscu')) . ' · ' . number_format($order->total, 2, ',', ' ') . ' PLN';

        // New order → notify manager + waiter (dine_in) + kitchen
        $roles = ['manager'];
        if ($order->type === 'dine_in') {
            $roles[] = 'waiter';
        }

        $this->sendToRoles($roles, $title, $body, '/manager/orders', $pub, $priv);
    }

    private function handleStatusChanged(OrderStatusChanged $event, string $pub, string $priv): void
    {
        $order     = $event->order;
        $newStatus = $event->newStatus;

        // Manager always gets push on every status change
        $roles = ['manager'];
        $url   = '/manager/orders';

        switch ($newStatus) {
            case 'paid':
                // Order accepted → notify kitchen
                $title  = 'Zamówienie do przygotowania';
                $body   = '#' . $order->order_number;
                $roles[] = 'kitchen';
                $url    = '/staff/kitchen';
                break;

            case 'ready':
                if ($order->type === 'delivery') {
                    // Ready for pickup → notify driver
                    $title   = 'Gotowe do odbioru';
                    $body    = '#' . $order->order_number . ' · ' . $order->delivery_address;
                    $roles[] = 'driver';
                    $url     = '/staff/driver';
                } else {
                    $title = 'Zamówienie gotowe';
                    $body  = '#' . $order->order_number;
                }
                break;

            default:
                // Other statuses — only manager
                $title = 'Status zamówienia zmieniony';
                $body  = '#' . $order->order_number . ' → ' . $newStatus;
                break;
        }

        $this->sendToRoles($roles, $title, $body, $url, $pub, $priv);
    }

    private function sendToRoles(array $roles, string $title, string $body, string $url, string $pub, string $priv): void
    {
        $subscriptions = PushSubscription::whereIn('role', $roles)->get();

        Log::info('Web Push: sending', [
            'roles' => $roles,
            'count' => $subscriptions->count(),
            'title' => $title,
        ]);

        foreach ($subscriptions as $sub) {
            try {
                $this->sendPush($sub, $title, $body, $url, $pub, $priv);
            } catch (\Exception $e) {
                Log::warning('Web Push failed', [
                    'endpoint' => substr($sub->endpoint, 0, 60),
                    'error'    => $e->getMessage(),
                ]);
                if (str_contains($e->getMessage(), '410') || str_contains($e->getMessage(), '404')) {
                    $sub->delete();
                }
            }
        }
    }

    private function sendPush(PushSubscription $sub, string $title, string $body, string $url, string $vapidPublic, string $vapidPrivate): void
    {
        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'icon'  => '/pwa-192x192.png',
            'badge' => '/pwa-192x192.png',
            'tag'   => 'roveto-order',
            'data'  => ['url' => $url],
        ]);

        $audience = parse_url($sub->endpoint, PHP_URL_SCHEME) . '://' . parse_url($sub->endpoint, PHP_URL_HOST);
        $expiry   = time() + 43200;
        $subject  = config('webpush.vapid.subject', 'mailto:' . env('MAIL_FROM_ADDRESS', 'admin@example.com'));

        $header   = $this->b64u(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims   = $this->b64u(json_encode(['aud' => $audience, 'exp' => $expiry, 'sub' => $subject]));
        $sigInput = $header . '.' . $claims;

        $keyResource = openssl_pkey_get_private($this->pemFromPrivateKey($vapidPrivate));
        openssl_sign($sigInput, $derSignature, $keyResource, OPENSSL_ALGO_SHA256);
        // ES256 JWT requires raw R||S (64 bytes), not DER-encoded signature
        $jwt = $sigInput . '.' . $this->b64u($this->derToRaw($derSignature));

        $headers = [
            'Authorization' => 'vapid t=' . $jwt . ', k=' . $vapidPublic,
            'TTL'           => '60',
        ];

        if ($sub->p256dh_key && $sub->auth_key) {
            [$encryptedPayload, $cryptoHeaders] = $this->encryptPayload($payload, $sub->p256dh_key, $sub->auth_key, $vapidPublic);
            $headers = array_merge($headers, $cryptoHeaders);
            $response = Http::withHeaders($headers)->withBody($encryptedPayload, 'application/octet-stream')->post($sub->endpoint);
        } else {
            $response = Http::withHeaders($headers)->post($sub->endpoint);
        }

        Log::info('Web Push: response', [
            'status'   => $response->status(),
            'body'     => substr($response->body(), 0, 200),
            'endpoint' => substr($sub->endpoint, 0, 60),
        ]);

        // Remove expired/invalid subscriptions
        if (in_array($response->status(), [404, 410])) {
            $sub->delete();
        }
    }

    private function encryptPayload(string $payload, string $p256dhKey, string $authKey, string $vapidPublic): array
    {
        $salt     = random_bytes(16);
        $localKey = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC, 'config' => $this->opensslConfig()]);
        $localDetails = openssl_pkey_get_details($localKey);

        $receiverPublicRaw = base64_decode(strtr($p256dhKey, '-_', '+/'));
        $authSecret        = base64_decode(strtr($authKey, '-_', '+/'));

        if (strlen($receiverPublicRaw) !== 65 || $receiverPublicRaw[0] !== "\x04") {
            throw new \RuntimeException('Invalid p256dh key format');
        }

        $receiverPem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode(
                "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"
                . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00"
                . $receiverPublicRaw
            ), 64)
            . "-----END PUBLIC KEY-----";

        $receiverKey  = openssl_pkey_get_public($receiverPem);
        $sharedSecret = openssl_pkey_derive($receiverKey, $localKey);

        if ($sharedSecret === false) {
            throw new \RuntimeException('ECDH key derivation failed');
        }

        $localPublicUncompressed = "\x04" . $localDetails['ec']['x'] . $localDetails['ec']['y'];

        // HKDF-Extract(salt=authSecret, ikm=sharedSecret) → PRK
        // HKDF-Expand(PRK, info="Content-Encoding: auth\0", 32) → key material
        $prkAuth = hash_hmac('sha256', $sharedSecret, $authSecret, true);
        $prk     = substr(hash_hmac('sha256', "Content-Encoding: auth\x00\x01", $prkAuth, true), 0, 32);

        $context = "P-256\x00"
            . pack('n', strlen($receiverPublicRaw)) . $receiverPublicRaw
            . pack('n', strlen($localPublicUncompressed)) . $localPublicUncompressed;

        // HKDF-Extract(salt=salt, ikm=prk) → prkSalt
        // HKDF-Expand to get CEK and nonce
        $prkSalt = hash_hmac('sha256', $prk, $salt, true);
        $cek     = substr(hash_hmac('sha256', "Content-Encoding: aesgcm\x00" . $context . "\x01", $prkSalt, true), 0, 16);
        $nonce   = substr(hash_hmac('sha256', "Content-Encoding: nonce\x00" . $context . "\x01", $prkSalt, true), 0, 12);

        $tag       = '';
        $encrypted = openssl_encrypt("\x00\x00" . $payload, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        return [
            $encrypted . $tag,
            [
                'Content-Encoding' => 'aesgcm',
                'Encryption'       => 'salt=' . $this->b64u($salt),
                'Crypto-Key'       => 'dh=' . $this->b64u($localPublicUncompressed) . '; p256ecdsa=' . $vapidPublic,
            ],
        ];
    }

    /** Convert DER-encoded ECDSA signature to raw R||S (64 bytes) for JWT ES256 */
    private function derToRaw(string $der): string
    {
        // DER: 0x30 <len> 0x02 <rLen> <r> 0x02 <sLen> <s>
        $offset = 2; // skip SEQUENCE tag + length
        $rLen = ord($der[$offset + 1]);
        $r = substr($der, $offset + 2, $rLen);
        $offset = $offset + 2 + $rLen;
        $sLen = ord($der[$offset + 1]);
        $s = substr($der, $offset + 2, $sLen);

        // Remove leading zero byte (added when high bit is set)
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        // Pad to 32 bytes
        return str_pad($r, 32, "\x00", STR_PAD_LEFT) . str_pad($s, 32, "\x00", STR_PAD_LEFT);
    }

    private function pemFromPrivateKey(string $base64UrlKey): string
    {
        $key = base64_decode(strtr($base64UrlKey, '-_', '+/'));
        // SEC1 EC private key: SEQUENCE(49) = version(3) + privateKey(34) + curveOID(12)
        $der = "\x30\x31\x02\x01\x01\x04\x20" . $key . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64) . "-----END EC PRIVATE KEY-----";
    }

    private function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function opensslConfig(): ?string
    {
        // On Windows/XAMPP openssl.cnf must be specified explicitly
        $candidates = [
            'D:/Programy/Xampp/apache/conf/openssl.cnf',
            '/etc/ssl/openssl.cnf',
            '/usr/lib/ssl/openssl.cnf',
        ];
        foreach ($candidates as $path) {
            if (file_exists($path)) return $path;
        }
        return null;
    }
}
