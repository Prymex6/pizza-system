<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Models\Tenant\Setting;
use App\Services\GoogleReviewsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Default settings with their types and descriptions.
     */
    protected array $settingsSchema = [
        // Restaurant info
        'restaurant_name' => ['type' => 'string', 'description' => 'Nazwa restauracji', 'group' => 'general'],
        'restaurant_owner_name' => ['type' => 'string', 'description' => 'Imię i nazwisko właściciela / nazwa firmy (do dokumentów prawnych)', 'group' => 'general'],
        'restaurant_phone' => ['type' => 'string', 'description' => 'Numer telefonu', 'group' => 'general'],
        'restaurant_email' => ['type' => 'string', 'description' => 'Adres e-mail', 'group' => 'general'],
        'restaurant_address' => ['type' => 'string', 'description' => 'Adres restauracji', 'group' => 'general'],
        'restaurant_description' => ['type' => 'string', 'description' => 'Opis restauracji', 'group' => 'general'],
        'restaurant_nip' => ['type' => 'string', 'description' => 'NIP restauracji (do faktur)', 'group' => 'general'],
        'google_place_id' => ['type' => 'string', 'description' => 'Google Place ID (do opinii z wizytówki Google)', 'group' => 'general'],

        // Appearance
        'logo_url' => ['type' => 'string', 'description' => 'URL logo restauracji', 'group' => 'appearance'],
        'favicon_url' => ['type' => 'string', 'description' => 'URL favicon', 'group' => 'appearance'],
        'hero_image_url' => ['type' => 'string', 'description' => 'URL zdjęcia hero na stronie głównej', 'group' => 'appearance'],
        'hero_title' => ['type' => 'string', 'description' => 'Tytuł na hero (domyślnie nazwa restauracji)', 'group' => 'appearance'],
        'hero_subtitle' => ['type' => 'string', 'description' => 'Podtytuł na hero', 'group' => 'appearance'],

        // About us
        'about_enabled' => ['type' => 'boolean', 'description' => 'Sekcja O nas włączona', 'group' => 'modules'],
        'about_title' => ['type' => 'string', 'description' => 'Tytuł sekcji O nas', 'group' => 'modules'],
        'about_text' => ['type' => 'string', 'description' => 'Treść sekcji O nas', 'group' => 'modules'],
        'about_image_url' => ['type' => 'string', 'description' => 'Zdjęcie w sekcji O nas', 'group' => 'modules'],

        // Gallery
        'gallery_enabled' => ['type' => 'boolean', 'description' => 'Galeria zdjęć włączona', 'group' => 'modules'],
        'gallery_title' => ['type' => 'string', 'description' => 'Tytuł sekcji galerii', 'group' => 'modules'],
        'gallery_images' => ['type' => 'json', 'description' => 'Zdjęcia w galerii (JSON)', 'group' => 'modules'],

        // Reservations
        'reservations_enabled' => ['type' => 'boolean', 'description' => 'Moduł rezerwacji stolików włączony', 'group' => 'modules'],
        'reservations_advance_days' => ['type' => 'integer', 'description' => 'Na ile dni wprzód można rezerwować', 'group' => 'modules'],
        'reservations_slot_duration' => ['type' => 'integer', 'description' => 'Czas trwania rezerwacji (min)', 'group' => 'modules'],

        // Working hours
        'opening_hours' => ['type' => 'json', 'description' => 'Godziny otwarcia', 'group' => 'hours'],
        'closed_days' => ['type' => 'json', 'description' => 'Konkretne dni zamknięcia (tablica dat YYYY-MM-DD)', 'group' => 'hours'],

        // Orders
        'min_order_value' => ['type' => 'string', 'description' => 'Minimalna wartość zamówienia (PLN)', 'group' => 'orders'],
        'order_auto_accept' => ['type' => 'boolean', 'description' => 'Automatyczne przyjmowanie zamówień', 'group' => 'orders'],
        'estimated_preparation_time' => ['type' => 'integer', 'description' => 'Szacowany czas przygotowania (min)', 'group' => 'orders'],
        'delivery_enabled' => ['type' => 'boolean', 'description' => 'Dostawa włączona', 'group' => 'orders'],
        'pickup_enabled' => ['type' => 'boolean', 'description' => 'Odbiór osobisty włączony', 'group' => 'orders'],
        'dine_in_enabled' => ['type' => 'boolean', 'description' => 'Zamówienia na miejscu włączone', 'group' => 'orders'],

        // Payments
        'payment_cash_enabled' => ['type' => 'boolean', 'description' => 'Płatność gotówką', 'group' => 'payments'],
        'payment_card_on_delivery_enabled' => ['type' => 'boolean', 'description' => 'Płatność kartą przy odbiorze', 'group' => 'payments'],
        'payment_online_enabled' => ['type' => 'boolean', 'description' => 'Płatność online (legacy)', 'group' => 'payments'],
        // Przelewy24
        'payment_p24_enabled' => ['type' => 'boolean', 'description' => 'Przelewy24 aktywne', 'group' => 'payments'],
        'p24_merchant_id' => ['type' => 'string', 'description' => 'Przelewy24 Merchant ID', 'group' => 'payments'],
        'p24_pos_id' => ['type' => 'string', 'description' => 'Przelewy24 POS ID', 'group' => 'payments'],
        'p24_api_key' => ['type' => 'encrypted', 'description' => 'Przelewy24 API Key', 'group' => 'payments'],
        'p24_crc' => ['type' => 'encrypted', 'description' => 'Przelewy24 CRC Key', 'group' => 'payments'],
        'p24_sandbox' => ['type' => 'boolean', 'description' => 'Przelewy24 tryb sandbox', 'group' => 'payments'],
        // PayU
        'payment_payu_enabled' => ['type' => 'boolean', 'description' => 'PayU aktywne', 'group' => 'payments'],
        'payu_pos_id' => ['type' => 'string', 'description' => 'PayU POS ID', 'group' => 'payments'],
        'payu_signature_key' => ['type' => 'encrypted', 'description' => 'PayU MD5 Signature Key', 'group' => 'payments'],
        'payu_client_id' => ['type' => 'string', 'description' => 'PayU OAuth Client ID', 'group' => 'payments'],
        'payu_client_secret' => ['type' => 'encrypted', 'description' => 'PayU OAuth Client Secret', 'group' => 'payments'],
        'payu_mode' => ['type' => 'string', 'description' => 'PayU tryb (sandbox/production)', 'group' => 'payments'],
        // Tpay
        'payment_tpay_enabled' => ['type' => 'boolean', 'description' => 'Tpay aktywne', 'group' => 'payments'],
        'tpay_client_id' => ['type' => 'string', 'description' => 'Tpay Client ID', 'group' => 'payments'],
        'tpay_client_secret' => ['type' => 'encrypted', 'description' => 'Tpay Client Secret', 'group' => 'payments'],
        'tpay_mode' => ['type' => 'string', 'description' => 'Tpay tryb (sandbox/production)', 'group' => 'payments'],
        // Stripe
        'payment_stripe_enabled' => ['type' => 'boolean', 'description' => 'Stripe aktywne', 'group' => 'payments'],
        'stripe_public_key' => ['type' => 'string', 'description' => 'Stripe Publishable Key', 'group' => 'payments'],
        'stripe_secret_key' => ['type' => 'encrypted', 'description' => 'Stripe Secret Key', 'group' => 'payments'],
        'stripe_webhook_secret' => ['type' => 'encrypted', 'description' => 'Stripe Webhook Secret', 'group' => 'payments'],

        // Printer
        'printer_enabled' => ['type' => 'boolean', 'description' => 'Drukowanie bonów włączone', 'group' => 'printer'],
        'printer_type' => ['type' => 'string', 'description' => 'Typ drukarki (bluetooth/network/usb)', 'group' => 'printer'],
        'printer_address' => ['type' => 'string', 'description' => 'Adres drukarki (IP lub MAC)', 'group' => 'printer'],

        // Notifications
        'notification_sound_enabled' => ['type' => 'boolean', 'description' => 'Dźwięk powiadomień', 'group' => 'notifications'],
        'notification_email_enabled' => ['type' => 'boolean', 'description' => 'Powiadomienia e-mail', 'group' => 'notifications'],
        'notification_email_address' => ['type' => 'string', 'description' => 'Adres e-mail do powiadomień', 'group' => 'notifications'],

        // SMS (SMSAPI.pl)
        'sms_enabled' => ['type' => 'boolean', 'description' => 'Powiadomienia SMS włączone', 'group' => 'sms'],
        'smsapi_token' => ['type' => 'encrypted', 'description' => 'Token API SMSAPI.pl (OAuth2)', 'group' => 'sms'],
        'sms_sender_name' => ['type' => 'string', 'description' => 'Nazwa nadawcy SMS (maks. 11 znaków)', 'group' => 'sms'],

        // Theme / custom styling (Level A + B)
        'theme_primary_color' => ['type' => 'string', 'description' => 'Główny kolor akcentu (HEX)', 'group' => 'appearance'],
        'theme_font' => ['type' => 'string', 'description' => 'Czcionka witryny', 'group' => 'appearance'],
        'custom_css' => ['type' => 'string', 'description' => 'Własny CSS witryny klienta', 'group' => 'appearance'],

        // Homepage blocks (Level C)
        'homepage_blocks' => ['type' => 'json', 'description' => 'Bloki strony głównej', 'group' => 'modules'],

        // Social media
        'facebook_url' => ['type' => 'string', 'description' => 'Link do Facebook', 'group' => 'social'],
        'instagram_url' => ['type' => 'string', 'description' => 'Link do Instagram', 'group' => 'social'],
        'tiktok_url' => ['type' => 'string', 'description' => 'Link do TikTok', 'group' => 'social'],

        // Analytics & integrations
        'google_analytics_id' => ['type' => 'string', 'description' => 'Google Analytics GA4 Measurement ID', 'group' => 'integrations'],
        'facebook_pixel_id' => ['type' => 'string', 'description' => 'Facebook Pixel ID', 'group' => 'integrations'],

        // SMTP per tenant (#12)
        'smtp_host' => ['type' => 'string', 'description' => 'Serwer SMTP', 'group' => 'smtp'],
        'smtp_port' => ['type' => 'integer', 'description' => 'Port SMTP', 'group' => 'smtp'],
        'smtp_username' => ['type' => 'string', 'description' => 'Login SMTP', 'group' => 'smtp'],
        'smtp_password' => ['type' => 'encrypted', 'description' => 'Hasło SMTP', 'group' => 'smtp'],
        'smtp_from_address' => ['type' => 'string', 'description' => 'Adres nadawcy e-mail', 'group' => 'smtp'],
        'smtp_from_name' => ['type' => 'string', 'description' => 'Nazwa nadawcy e-mail', 'group' => 'smtp'],
        'smtp_encryption' => ['type' => 'string', 'description' => 'Szyfrowanie (tls/ssl/none)', 'group' => 'smtp'],

        // Loyalty program configuration
        'loyalty_enabled'                  => ['type' => 'boolean', 'description' => 'Program lojalnościowy włączony', 'group' => 'loyalty'],
        'loyalty_earn_mode'                => ['type' => 'string', 'description' => 'Tryb naliczania: per_pln / per_order / tiered', 'group' => 'loyalty'],
        'loyalty_points_per_pln'           => ['type' => 'integer', 'description' => 'Punkty za 1 PLN zamówienia', 'group' => 'loyalty'],
        'loyalty_points_per_order'         => ['type' => 'integer', 'description' => 'Stała liczba pkt za zamówienie (tryb per_order)', 'group' => 'loyalty'],
        'loyalty_tiers'                    => ['type' => 'json', 'description' => 'Progi poziomów lojalnościowych', 'group' => 'loyalty'],
        'loyalty_tier_basis'               => ['type' => 'string', 'description' => 'Podstawa poziomów: ever_earned / current_balance', 'group' => 'loyalty'],
        'loyalty_points_expiry_days'       => ['type' => 'integer', 'description' => 'Wygasanie punktów (dni, 0 = brak)', 'group' => 'loyalty'],
        'loyalty_expiry_warning_days'      => ['type' => 'integer', 'description' => 'Ostrzeżenie o wygasaniu (dni przed)', 'group' => 'loyalty'],
        'loyalty_bonus_registration'       => ['type' => 'integer', 'description' => 'Bonus za rejestrację (pkt)', 'group' => 'loyalty'],
        'loyalty_bonus_first_order'        => ['type' => 'integer', 'description' => 'Bonus za pierwsze zamówienie (pkt)', 'group' => 'loyalty'],
        'loyalty_bonus_referral'           => ['type' => 'integer', 'description' => 'Bonus za polecenie (pkt)', 'group' => 'loyalty'],
        'loyalty_bonus_birthday'           => ['type' => 'integer', 'description' => 'Bonus urodzinowy (pkt)', 'group' => 'loyalty'],
        'loyalty_bonus_birthday_multiplier'=> ['type' => 'float', 'description' => 'Mnożnik zamówień w miesiącu urodzin', 'group' => 'loyalty'],

        // Regulations / terms (#14)
        'terms_content' => ['type' => 'string', 'description' => 'Treść regulaminu (HTML)', 'group' => 'regulations'],
        'privacy_content' => ['type' => 'string', 'description' => 'Treść polityki prywatności (HTML)', 'group' => 'regulations'],

        // Vacation / maintenance mode (#34)
        'vacation_mode' => ['type' => 'boolean', 'description' => 'Tryb urlopowy – blokada zamówień', 'group' => 'general'],
        'vacation_message' => ['type' => 'string', 'description' => 'Komunikat wyświetlany podczas trybu urlopowego', 'group' => 'general'],

        // Driver GPS tracking
        'driver_tracking_enabled' => ['type' => 'boolean', 'description' => 'Śledzenie kierowcy GPS włączone', 'group' => 'orders'],
    ];

    public function index()
    {
        $settings = Setting::getAllAsArray();

        // Fill in defaults for missing settings
        $defaults = $this->getDefaults();
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $settings)) {
                $settings[$key] = $value;
            }
        }

        return Inertia::render('Tenant/Manager/Settings/Index', [
            'settings' => $settings,
            'schema' => $this->settingsSchema,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'restaurant_name' => 'nullable|string|max:255',
            'restaurant_owner_name' => 'nullable|string|max:500',
            'restaurant_phone' => 'nullable|string|max:30',
            'restaurant_email' => 'nullable|email|max:255',
            'restaurant_address' => 'nullable|string|max:500',
            'restaurant_description' => 'nullable|string|max:1000',
            'restaurant_nip' => 'nullable|string|max:20',
            'google_place_id' => 'nullable|string|max:255',
            // Appearance
            'logo_url' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|string|max:500',
            'hero_image_url' => 'nullable|string|max:500',
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:500',
            // Modules
            'about_enabled' => 'nullable|boolean',
            'about_title' => 'nullable|string|max:255',
            'about_text' => 'nullable|string|max:5000',
            'about_image_url' => 'nullable|string|max:500',
            'gallery_enabled' => 'nullable|boolean',
            'gallery_title' => 'nullable|string|max:255',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|string|max:500',
            'reservations_enabled' => 'nullable|boolean',
            'reservations_advance_days' => 'nullable|integer|min:1|max:90',
            'reservations_slot_duration' => 'nullable|integer|min:30|max:300',
            // Hours & orders
            'opening_hours' => 'nullable|array',
            'opening_hours.*.open' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'opening_hours.*.close' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'opening_hours.*.enabled' => ['nullable', 'boolean'],
            'min_order_value' => 'nullable|numeric|min:0',
            'order_auto_accept' => 'nullable|boolean',
            'estimated_preparation_time' => 'nullable|integer|min:5|max:120',
            'delivery_enabled' => 'nullable|boolean',
            'pickup_enabled' => 'nullable|boolean',
            'dine_in_enabled' => 'nullable|boolean',
            'payment_cash_enabled' => 'nullable|boolean',
            'payment_card_on_delivery_enabled' => 'nullable|boolean',
            'payment_online_enabled' => 'nullable|boolean',
            'payment_p24_enabled' => 'nullable|boolean',
            'p24_merchant_id' => 'nullable|string|max:100',
            'p24_pos_id' => 'nullable|string|max:100',
            'p24_api_key' => 'nullable|string|max:200',
            'p24_crc' => 'nullable|string|max:100',
            'p24_sandbox' => 'nullable|boolean',
            'payment_payu_enabled' => 'nullable|boolean',
            'payu_pos_id' => 'nullable|string|max:100',
            'payu_signature_key' => 'nullable|string|max:200',
            'payu_client_id' => 'nullable|string|max:200',
            'payu_client_secret' => 'nullable|string|max:200',
            'payu_mode' => 'nullable|string|in:sandbox,production',
            'payment_tpay_enabled' => 'nullable|boolean',
            'tpay_client_id' => 'nullable|string|max:200',
            'tpay_client_secret' => 'nullable|string|max:200',
            'tpay_mode' => 'nullable|string|in:sandbox,production',
            'payment_stripe_enabled' => 'nullable|boolean',
            'stripe_public_key' => 'nullable|string|max:200',
            'stripe_secret_key' => 'nullable|string|max:200',
            'stripe_webhook_secret' => 'nullable|string|max:200',
            'printer_enabled' => 'nullable|boolean',
            'printer_type' => 'nullable|string|in:bluetooth,network,usb',
            'printer_address' => 'nullable|string|max:255',
            'notification_sound_enabled' => 'nullable|boolean',
            'notification_email_enabled' => 'nullable|boolean',
            'notification_email_address' => 'nullable|email|max:255',
            // SMS
            'sms_enabled' => 'nullable|boolean',
            'smsapi_token' => 'nullable|string|max:500',
            'sms_sender_name' => 'nullable|string|max:11',
            // Theme
            'theme_primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_font' => 'nullable|string|in:inter,roboto,merriweather,playfair',
            'custom_css' => ['nullable', 'string', 'max:50000', 'not_regex:/<\/style/i'],
            // Social media
            'facebook_url' => ['nullable', 'url', 'max:500'],
            'instagram_url' => ['nullable', 'url', 'max:500'],
            'tiktok_url' => ['nullable', 'url', 'max:500'],
            // Analytics & integrations
            'google_analytics_id' => ['nullable', 'string', 'max:50', 'regex:/^(G-[A-Z0-9]{4,20}|UA-\d{4,12}-\d{1,4}|GT-[A-Z0-9]{4,20}|AW-[0-9]{8,12})$/'],
            'facebook_pixel_id' => ['nullable', 'string', 'max:20', 'regex:/^\d{10,20}$/'],
            // SMTP
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_from_address' => 'nullable|email|max:255',
            'smtp_from_name' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|string|in:tls,ssl,none',
            // Loyalty v2
            'loyalty_enabled'                   => 'nullable|boolean',
            'loyalty_earn_mode'                 => 'nullable|string|in:per_pln,per_order,tiered',
            'loyalty_points_per_pln'            => 'nullable|integer|min:1|max:100',
            'loyalty_points_per_order'          => 'nullable|integer|min:0',
            'loyalty_tiers'                     => 'nullable|array',
            'loyalty_tier_basis'                => 'nullable|string|in:ever_earned,current_balance',
            'loyalty_points_expiry_days'        => 'nullable|integer|min:0',
            'loyalty_expiry_warning_days'       => 'nullable|integer|min:0',
            'loyalty_bonus_registration'        => 'nullable|integer|min:0',
            'loyalty_bonus_first_order'         => 'nullable|integer|min:0',
            'loyalty_bonus_referral'            => 'nullable|integer|min:0',
            'loyalty_bonus_birthday'            => 'nullable|integer|min:0',
            'loyalty_bonus_birthday_multiplier' => 'nullable|numeric|min:1|max:10',
            // Terms / Privacy
            'terms_content' => 'nullable|string|max:100000',
            'privacy_content' => 'nullable|string|max:100000',
            // Vacation mode
            'vacation_mode' => 'nullable|boolean',
            'vacation_message' => 'nullable|string|max:500',
            // Driver GPS tracking
            'driver_tracking_enabled' => 'nullable|boolean',
            // Specific closure days (#31)
            'closed_days' => 'nullable|array',
            'closed_days.*' => 'date_format:Y-m-d',
            // Homepage blocks
            'homepage_blocks' => 'nullable|array',
            'homepage_blocks.*.id' => 'nullable|string|max:64',
            'homepage_blocks.*.type' => 'nullable|string|in:announcement,promo_text,cta',
            'homepage_blocks.*.enabled' => 'nullable|boolean',
            'homepage_blocks.*.title' => 'nullable|string|max:255',
            'homepage_blocks.*.content' => 'nullable|string|max:2000',
            'homepage_blocks.*.bg_color' => 'nullable|string|max:20',
            'homepage_blocks.*.link_url' => ['nullable', 'string', 'max:500', 'not_regex:/^javascript:/i'],
            'homepage_blocks.*.link_text' => 'nullable|string|max:100',
        ]);

        if (array_key_exists('restaurant_phone', $data)) {
            $data['restaurant_phone'] = PhoneHelper::normalize($data['restaurant_phone']) ?? '';
        }

        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $this->settingsSchema)) {
                continue;
            }

            $schema = $this->settingsSchema[$key];
            Setting::set($key, $value, $schema['type'], $schema['description']);
        }

        if (array_key_exists('google_place_id', $data)) {
            app(GoogleReviewsService::class)->clearCache();
        }

        Log::info('Settings: ustawienia zapisane', [
            'keys'       => array_keys($data),
            'manager_id' => auth('tenant')->id(),
        ]);

        return redirect()->route('tenant.manager.settings.index')
            ->with('success', 'Ustawienia zostały zapisane.');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file'  => 'required|file|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'field' => 'required|string',
        ]);

        $subfolder = match(true) {
            str_starts_with($request->field, 'product') => 'products',
            str_starts_with($request->field, 'gallery') => 'gallery',
            default                                      => 'settings',
        };

        $uploadedFile = $request->file('file');
        $filename     = \Illuminate\Support\Str::uuid() . '.jpg';
        $tenantId     = tenancy()->tenant?->id;

        if ($tenantId) {
            // Save to storage/tenants/{id}/media/{subfolder}/ — accessible via /tenants/{id}/{subfolder}/
            $storage      = app(\App\Services\TenantStorageService::class);
            $absolutePath = $storage->uploadDestination($tenantId, $subfolder, $filename);
            $url          = $storage->publicUrl($tenantId, $subfolder, $filename);
        } else {
            // Fallback: legacy public/uploads/ path (should not happen in normal tenant flow)
            $destDir      = public_path("uploads/{$subfolder}");
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $absolutePath = "{$destDir}/{$filename}";
            $url          = "/uploads/{$subfolder}/{$filename}";
        }

        $this->optimizeAndSave($uploadedFile->getRealPath(), $uploadedFile->getMimeType(), $absolutePath);

        return response()->json(['url' => $url]);
    }

    private function optimizeAndSave(string $sourcePath, string $mimeType, string $destPath): void
    {
        if (!extension_loaded('gd')) {
            // GD not available — just copy as-is
            copy($sourcePath, $destPath);
            return;
        }

        $src = match($mimeType) {
            'image/png'  => @imagecreatefrompng($sourcePath),
            'image/gif'  => @imagecreatefromgif($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            default      => @imagecreatefromjpeg($sourcePath),
        };

        if (!$src) {
            copy($sourcePath, $destPath);
            return;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        $maxDim = 1200;

        if ($origW > $maxDim || $origH > $maxDim) {
            $ratio = min($maxDim / $origW, $maxDim / $origH);
            $newW  = (int) round($origW * $ratio);
            $newH  = (int) round($origH * $ratio);
            $dst   = imagecreatetruecolor($newW, $newH);
            // Preserve transparency for PNG/GIF
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
        }

        imagejpeg($src, $destPath, 80);
        imagedestroy($src);
    }

    protected function getDefaults(): array
    {
        return [
            'restaurant_name' => '',
            'restaurant_owner_name' => '',
            'restaurant_phone' => '',
            'restaurant_email' => '',
            'restaurant_address' => '',
            'restaurant_description' => '',
            'google_place_id' => '',
            // Appearance
            'logo_url' => '',
            'favicon_url' => '',
            'hero_image_url' => '',
            'hero_title' => '',
            'hero_subtitle' => '',
            // Modules
            'about_enabled' => false,
            'about_title' => 'O nas',
            'about_text' => '',
            'about_image_url' => '',
            'gallery_enabled' => false,
            'gallery_title' => 'Galeria',
            'gallery_images' => [],
            'reservations_enabled' => false,
            'reservations_advance_days' => 14,
            'reservations_slot_duration' => 120,
            'opening_hours' => [
                'monday' => ['open' => '10:00', 'close' => '22:00', 'enabled' => true],
                'tuesday' => ['open' => '10:00', 'close' => '22:00', 'enabled' => true],
                'wednesday' => ['open' => '10:00', 'close' => '22:00', 'enabled' => true],
                'thursday' => ['open' => '10:00', 'close' => '22:00', 'enabled' => true],
                'friday' => ['open' => '10:00', 'close' => '23:00', 'enabled' => true],
                'saturday' => ['open' => '11:00', 'close' => '23:00', 'enabled' => true],
                'sunday' => ['open' => '11:00', 'close' => '21:00', 'enabled' => true],
            ],
            'min_order_value' => '30.00',
            'order_auto_accept' => false,
            'estimated_preparation_time' => 30,
            'delivery_enabled' => true,
            'pickup_enabled' => true,
            'dine_in_enabled' => false,
            'payment_cash_enabled' => true,
            'payment_card_on_delivery_enabled' => false,
            'payment_online_enabled' => false,
            'p24_merchant_id' => '',
            'p24_pos_id' => '',
            'p24_crc' => '',
            'p24_sandbox' => true,
            'printer_enabled' => false,
            'printer_type' => 'network',
            'printer_address' => '',
            'notification_sound_enabled' => true,
            'notification_email_enabled' => false,
            'notification_email_address' => '',
            // SMS
            'sms_enabled' => false,
            'smsapi_token' => '',
            'sms_sender_name' => 'Restauracja',
            // Theme
            'theme_primary_color' => '#b91c1c',
            'theme_font' => 'inter',
            'custom_css' => '',
            // Homepage blocks
            'homepage_blocks' => [],
            // Social media
            'facebook_url' => '',
            'instagram_url' => '',
            'tiktok_url' => '',
            // Analytics
            'google_analytics_id' => '',
            'facebook_pixel_id' => '',
            // SMTP
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_address' => '',
            'smtp_from_name' => '',
            'smtp_encryption' => 'tls',
            // Loyalty v2
            'loyalty_enabled'                   => false,
            'loyalty_earn_mode'                 => 'per_pln',
            'loyalty_points_per_pln'            => 1,
            'loyalty_points_per_order'          => 10,
            'loyalty_tier_basis'                => 'ever_earned',
            'loyalty_points_expiry_days'        => 0,
            'loyalty_expiry_warning_days'       => 7,
            'loyalty_bonus_registration'        => 50,
            'loyalty_bonus_first_order'         => 100,
            'loyalty_bonus_referral'            => 200,
            'loyalty_bonus_birthday'            => 150,
            'loyalty_bonus_birthday_multiplier' => 2.0,
            'loyalty_tiers'                     => [
                'bronze'   => ['min' => 0,     'name' => 'Brąz',    'color' => '#cd7f32', 'multiplier' => 1.0,  'monthly_bonus' => 0,    'delivery_bonus' => 0],
                'silver'   => ['min' => 500,   'name' => 'Srebro',  'color' => '#9ca3af', 'multiplier' => 1.25, 'monthly_bonus' => 50,   'delivery_bonus' => 10],
                'gold'     => ['min' => 1500,  'name' => 'Złoto',   'color' => '#f59e0b', 'multiplier' => 1.5,  'monthly_bonus' => 150,  'delivery_bonus' => 20],
                'platinum' => ['min' => 4000,  'name' => 'Platyna', 'color' => '#60a5fa', 'multiplier' => 2.0,  'monthly_bonus' => 400,  'delivery_bonus' => 999],
                'diamond'  => ['min' => 10000, 'name' => 'Diament', 'color' => '#a78bfa', 'multiplier' => 3.0,  'monthly_bonus' => 1000, 'delivery_bonus' => 999],
            ],
            // Terms / Privacy
            'terms_content' => '',
            'privacy_content' => '',
            // Vacation mode
            'vacation_mode' => false,
            'vacation_message' => 'Restauracja jest chwilowo niedostępna. Zapraszamy wkrótce!',
            'closed_days' => [],
            // Driver GPS tracking
            'driver_tracking_enabled' => false,
        ];
    }

    public function testSms(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        try {
            $smsService = app(\App\Services\SmsService::class);
            $sent = $smsService->send(
                $request->phone,
                'Wiadomość testowa z systemu ' . config('app.name') . '. Konfiguracja SMS działa poprawnie.'
            );

            if ($sent) {
                return response()->json(['success' => true, 'message' => 'SMS testowy został wysłany.']);
            }
            return response()->json(['success' => false, 'message' => 'SMS nie został wysłany. Sprawdź konfigurację SMSAPI.'], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Błąd SMS: ' . $e->getMessage()], 422);
        }
    }

    public function testSmtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $encryption = Setting::get('smtp_encryption', 'tls');

            config(['mail.mailers.tenant_smtp_test' => [
                'transport'  => 'smtp',
                'host'       => Setting::get('smtp_host'),
                'port'       => (int) Setting::get('smtp_port', 587),
                'encryption' => $encryption !== 'none' ? $encryption : null,
                'username'   => Setting::get('smtp_username'),
                'password'   => Setting::get('smtp_password'),
                'from'       => [
                    'address' => Setting::get('smtp_from_address', Setting::get('restaurant_email', 'noreply@example.com')),
                    'name'    => Setting::get('smtp_from_name', Setting::get('restaurant_name', 'Restauracja')),
                ],
            ]]);

            \Illuminate\Support\Facades\Mail::mailer('tenant_smtp_test')
                ->raw('To jest wiadomość testowa z systemu ' . config('app.name') . '.', function ($message) use ($request) {
                    $message->to($request->email)->subject('Test konfiguracji SMTP');
                });

            return response()->json(['success' => true, 'message' => 'Wiadomość testowa została wysłana.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Błąd SMTP: ' . $e->getMessage()], 422);
        }
    }
}
