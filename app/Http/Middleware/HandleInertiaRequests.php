<?php

namespace App\Http\Middleware;

use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $this->getAuthUser(),
                'customer' => $this->getAuthCustomer(),
            ],
            'flash' => [
                'success'         => fn () => $request->session()->get('success'),
                'error'           => fn () => $request->session()->get('error'),
                'info'            => fn () => $request->session()->get('info'),
                'contact_success' => fn () => $request->session()->get('contact_success'),
            ],
            'tenant' => fn () => $this->getTenantData(),
            'pendingReservationsCount' => fn () => $this->getPendingReservationsCount(),
            'landlordUnreadSupportCount' => fn () => $this->getLandlordUnreadSupportCount(),
            'google_maps_api_key' => fn () => auth('tenant')->check() ? config('services.google_maps.api_key', '') : '',
            'impersonating' => fn () => $request->session()->get('impersonating', false),
            'app_version' => fn () => $this->getTenantVersion(),
            'app_name' => config('app.name'),
            'vapidPublicKey' => config('webpush.vapid.public_key', ''),
        ];
    }

    protected function getAuthUser(): ?array
    {
        if ($user = auth('tenant')->user()) {
            $permissions = $user->role === 'manager'
                ? array_keys(\App\Http\Controllers\Tenant\Manager\RolePermissionsController::PERMISSIONS)
                : \App\Models\Tenant\RolePermission::permissionsFor($user->role);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $permissions,
            ];
        }

        if ($user = auth('super_admin')->user()) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'super_admin',
            ];
        }

        return null;
    }

    protected function getAuthCustomer(): ?array
    {
        if (!tenancy()->initialized) {
            return null;
        }

        if ($customer = auth('customer')->user()) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'delivery_address' => $customer->delivery_address,
                'delivery_city' => $customer->delivery_city,
                'delivery_postal_code' => $customer->delivery_postal_code,
                'avatar' => $customer->avatar,
            ];
        }

        return null;
    }

    protected function getTenantData(): ?array
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return null;
            }

            // Single query instead of ~39 individual Setting::get() calls
            $s = Setting::getAllAsArray();

            $bool = fn ($key, $default) => filter_var($s[$key] ?? $default, FILTER_VALIDATE_BOOLEAN);

            return [
                'id' => tenancy()->tenant?->id,
                'name' => $s['restaurant_name'] ?? config('app.name'),
                'phone' => $s['restaurant_phone'] ?? null,
                'email' => $s['restaurant_email'] ?? null,
                'address' => $s['restaurant_address'] ?? null,
                'description' => $s['restaurant_description'] ?? null,
                'opening_hours' => isset($s['opening_hours']) ? (is_string($s['opening_hours']) ? json_decode($s['opening_hours'], true) : $s['opening_hours']) : null,
                'delivery_enabled' => $bool('delivery_enabled', true),
                'pickup_enabled' => $bool('pickup_enabled', true),
                'dine_in_enabled' => $bool('dine_in_enabled', false),
                'logo_url' => $s['logo_url'] ?? null,
                'favicon_url' => $s['favicon_url'] ?? null,
                'hero_image_url' => $s['hero_image_url'] ?? null,
                'hero_title' => $s['hero_title'] ?? null,
                'hero_subtitle' => $s['hero_subtitle'] ?? null,
                'about_enabled' => $bool('about_enabled', false),
                'about_title' => $s['about_title'] ?? 'O nas',
                'about_text' => $s['about_text'] ?? null,
                'about_image_url' => $s['about_image_url'] ?? null,
                'gallery_enabled' => $bool('gallery_enabled', false),
                'gallery_title' => $s['gallery_title'] ?? 'Galeria',
                'gallery_images' => isset($s['gallery_images']) ? (is_string($s['gallery_images']) ? json_decode($s['gallery_images'], true) : $s['gallery_images']) : null,
                'reservations_enabled' => $bool('reservations_enabled', false),
                'google_place_id' => $s['google_place_id'] ?? null,
                'theme_primary_color' => $s['theme_primary_color'] ?? '#b91c1c',
                'theme_font' => $s['theme_font'] ?? 'inter',
                'custom_css' => $s['custom_css'] ?? '',
                'homepage_blocks' => isset($s['homepage_blocks']) ? (is_string($s['homepage_blocks']) ? json_decode($s['homepage_blocks'], true) : $s['homepage_blocks']) : null,
                'facebook_url' => $s['facebook_url'] ?? null,
                'instagram_url' => $s['instagram_url'] ?? null,
                'tiktok_url' => $s['tiktok_url'] ?? null,
                'google_analytics_id' => $s['google_analytics_id'] ?? null,
                'facebook_pixel_id' => $s['facebook_pixel_id'] ?? null,
                'vacation_mode' => $bool('vacation_mode', false),
                'vacation_message' => $s['vacation_message'] ?? '',
                'orders_paused' => $bool('orders_paused', false),
                'is_open_now' => $this->computeIsOpenNow($s),
                'estimated_preparation_time' => isset($s['estimated_preparation_time']) ? (int)$s['estimated_preparation_time'] : null,
                'closed_days' => isset($s['closed_days']) ? (is_string($s['closed_days']) ? json_decode($s['closed_days'], true) : $s['closed_days']) : [],
                'loyalty_enabled' => $bool('loyalty_enabled', false),
            ];
        } catch (\Exception $e) {
            return ['name' => config('app.name')];
        }
    }

    protected function computeIsOpenNow(array $s): bool
    {
        if (filter_var($s['vacation_mode'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $timezone = $s['timezone'] ?? 'Europe/Warsaw';
        $now = now()->setTimezone($timezone);

        // Check specific closure days (#31)
        $closedDays = $s['closed_days'] ?? null;
        if (is_string($closedDays)) {
            $closedDays = json_decode($closedDays, true);
        }
        if (!empty($closedDays) && in_array($now->format('Y-m-d'), $closedDays)) {
            return false;
        }

        $hours = $s['opening_hours'] ?? null;
        if (is_string($hours)) {
            $hours = json_decode($hours, true);
        }
        if (empty($hours)) {
            return true; // no hours configured = always open
        }

        $dayName = strtolower($now->format('l')); // monday, tuesday...
        $dayData = $hours[$dayName] ?? null;

        $isEnabled = isset($dayData['enabled']) ? (bool)$dayData['enabled'] : !($dayData['closed'] ?? false);
        if (!$dayData || !$isEnabled) {
            return false;
        }

        $openMinutes  = $this->timeToMinutes($dayData['open'] ?? '00:00');
        $closeMinutes = $this->timeToMinutes($dayData['close'] ?? '23:59');
        // 00:00 close = midnight = end of day (1440 min), not start of day
        if ($closeMinutes === 0) $closeMinutes = 24 * 60;
        $currentMinutes = $now->hour * 60 + $now->minute;

        return $currentMinutes >= $openMinutes && $currentMinutes < $closeMinutes;
    }

    protected function getTenantVersion(): string
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return 'stable';
            }
            return tenancy()->tenant?->version ?? 'stable';
        } catch (\Exception $e) {
            return 'stable';
        }
    }

    protected function getPendingReservationsCount(): int
    {
        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                return 0;
            }
            return \App\Models\Tenant\Reservation::where('status', 'pending')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function getLandlordUnreadSupportCount(): int
    {
        try {
            if (!auth('super_admin')->check()) {
                return 0;
            }
            return \App\Models\Landlord\SupportTicket::where('unread_by_admin', true)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function timeToMinutes(string $time): int
    {
        [$h, $m] = explode(':', $time . ':00');
        return (int)$h * 60 + (int)$m;
    }
}
