<?php

namespace App\Tenancy\Bootstrappers;

use App\Models\Tenant\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class TenantMailBootstrapper implements TenancyBootstrapper
{
    protected array $originalConfig = [];

    public function bootstrap(Tenant $tenant): void
    {
        try {
            $fromAddress = Setting::get('smtp_from_address') ?: Config::get('mail.from.address');
            $name        = Setting::get('restaurant_name');
        } catch (\Throwable $e) {
            // Tabela tenant_settings jeszcze nie istnieje (np. podczas pierwszej migracji)
            return;
        }

        $this->originalConfig = [
            'mail.from.address' => Config::get('mail.from.address'),
            'mail.from.name'    => Config::get('mail.from.name'),
        ];

        // Używaj smtp_from_address z ustawień tenanta, lub systemowego noreply
        if (!$fromAddress) {
            $fromAddress = Config::get('mail.from.address');
        }

        Config::set([
            'mail.from.address' => $fromAddress,
            'mail.from.name'    => $name ?: Config::get('mail.from.name'),
        ]);
    }

    public function revert(): void
    {
        if (empty($this->originalConfig)) {
            return;
        }

        Config::set($this->originalConfig);
        $this->originalConfig = [];
    }
}
