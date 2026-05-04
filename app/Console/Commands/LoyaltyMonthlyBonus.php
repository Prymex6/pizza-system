<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Landlord\Tenant;

class LoyaltyMonthlyBonus extends Command
{
    protected $signature   = 'loyalty:monthly-bonus';
    protected $description = 'Award monthly tier bonuses to all eligible customers (runs for all tenants)';

    public function handle(): int
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $service = app(\App\Services\LoyaltyService::class);
                $count   = $service->awardMonthlyBonus();

                $this->line("[{$tenant->id}] Monthly bonus awarded to {$count} customers");
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] Error: " . $e->getMessage());
            }

            tenancy()->end();
        }

        return self::SUCCESS;
    }
}
