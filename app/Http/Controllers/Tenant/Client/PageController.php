<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Setting;
use Inertia\Inertia;

class PageController extends Controller
{
    public function terms()
    {
        return Inertia::render('Tenant/Client/Pages/Terms', [
            'content' => $this->resolve(Setting::get('terms_content', '')),
        ]);
    }

    public function privacy()
    {
        return Inertia::render('Tenant/Client/Pages/Privacy', [
            'content' => $this->resolve(Setting::get('privacy_content', '')),
        ]);
    }

    private function resolve(?string $content): string
    {
        $tokens = [
            '{restaurant_name}'        => Setting::get('restaurant_name', ''),
            '{restaurant_owner_name}'  => Setting::get('restaurant_owner_name', Setting::get('restaurant_name', '')),
            '{restaurant_address}'     => Setting::get('restaurant_address', ''),
            '{restaurant_phone}'       => PhoneHelper::format(Setting::get('restaurant_phone', '')),
            '{restaurant_email}'       => Setting::get('restaurant_email', ''),
            '{restaurant_nip}'         => Setting::get('restaurant_nip', ''),
            '{year}'                   => date('Y'),
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $content ?? '');
    }
}
