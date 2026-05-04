<?php

namespace App\Tenancy\Bootstrappers;

use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class SingleDatabaseBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        $prefix = 't_' . substr(str_replace('-', '', $tenant->getTenantKey()), 0, 8) . '_';
        $this->applyPrefix($prefix);
    }

    public function revert(): void
    {
        $this->applyPrefix('');
    }

    private function applyPrefix(string $prefix): void
    {
        // Only prefix the default (mysql) connection — never the central connection used by sessions/cache
        $connName = config('database.default');
        config(["database.connections.{$connName}.prefix" => $prefix]);
        $conn = DB::connection($connName);
        $conn->setTablePrefix($prefix);
        $conn->getQueryGrammar()->setTablePrefix($prefix);
    }
}
