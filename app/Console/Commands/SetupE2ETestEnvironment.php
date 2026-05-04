<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use Illuminate\Console\Command;
use Stancl\Tenancy\Database\Models\Domain;

class SetupE2ETestEnvironment extends Command
{
    protected $signature   = 'e2e:setup {--fresh : Re-seed tenant data (does not drop DB)}';
    protected $description = 'Set up test environment for Playwright E2E tests';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('⛔ This command must NOT run in production.');
            return self::FAILURE;
        }

        $this->info('🧪 Setting up E2E test environment...');

        // 1. Ensure landlord data (plans + super admin)
        $this->call('db:seed', ['--class' => 'LandlordSeeder', '--force' => true]);

        // 2. Find the tenant at pizza.localhost (the development tenant)
        $domain = 'pizza.localhost';
        $domainRecord = Domain::where('domain', $domain)->first();

        if (! $domainRecord) {
            $this->error("No tenant found at {$domain}. Create a tenant with that domain first.");
            return self::FAILURE;
        }

        $tenant = Tenant::find($domainRecord->tenant_id);

        if (! $tenant) {
            $this->error("Tenant record not found for domain {$domain}.");
            return self::FAILURE;
        }

        $this->info("  Using tenant: {$tenant->id} ({$tenant->name}) → {$domain}");

        // Ensure tenant is active (tests may have suspended it)
        $tenant->update(['status' => 'active']);

        // 3. Run tenant migrations
        $this->info('  Running tenant migrations...');
        tenancy()->initialize($tenant);

        $this->call('tenants:migrate', [
            '--tenants' => [$tenant->id],
            '--force'   => true,
        ]);

        // Re-initialize tenancy after migrate (tenants:migrate may have ended it)
        tenancy()->initialize($tenant);

        // 4. Clear tenant cache (rate limits, etc.)
        \Illuminate\Support\Facades\DB::table('cache')->truncate();
        \Illuminate\Support\Facades\DB::table('cache_locks')->truncate();

        // Clean up e2e-created test customers (e.g. from E23.2.3 registration test)
        \Illuminate\Support\Facades\DB::table('customers')
            ->where('email', 'like', 'e2e.%@example.com')
            ->delete();

        // Clean up e2e-created test staff employees (e.g. from E7.1.4)
        \Illuminate\Support\Facades\DB::table('users')
            ->where('email', 'like', 'e2e.%@test.com')
            ->delete();

        // Clean up e2e-created test products (e.g. from E5.1.4)
        \Illuminate\Support\Facades\DB::table('products')
            ->where('name', 'like', '%E2E%')
            ->delete();

        // 5. Seed tenant data
        $this->info('  Seeding tenant data...');
        $this->call('db:seed', [
            '--class' => 'TenantSeeder',
            '--force' => true,
        ]);

        tenancy()->end();

        // 5. Output connection info for Playwright
        $this->newLine();
        $this->info('✅ E2E test environment ready!');
        $this->table(
            ['Key', 'Value'],
            [
                ['Landlord URL',    'http://localhost:8000'],
                ['Tenant URL',      "http://{$domain}:8000"],
                ['Super Admin',     env('ADMIN_EMAIL', 'admin@example.com') . ' / password'],
                ['Manager',         'manager@example.com / password'],
                ['Chef',            'chef@example.com / password'],
                ['Waiter',          'waiter@example.com / password'],
                ['Driver',          'driver@example.com / password'],
                ['Customer 1',      'klient@example.pl / password'],
                ['Customer 2',      'maria@example.pl / password'],
                ['Tenant ID',       $tenant->id],
                ['Tenant Domain',   $domain],
            ]
        );

        return self::SUCCESS;
    }
}
