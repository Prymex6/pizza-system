<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\BackupService;
use Illuminate\Console\Command;

class TenantDatabaseBackupCommand extends Command
{
    protected $signature   = 'backup:tenants {--tenant= : Backup only this tenant ID}';
    protected $description = 'Backup tenant databases (all or a single tenant)';

    public function handle(BackupService $backup): int
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("Tenant {$tenantId} not found.");
                return self::FAILURE;
            }
            $tenants = collect([$tenant]);
        } else {
            $tenants = Tenant::where('status', 'active')->get();
        }

        $ok  = 0;
        $err = 0;

        foreach ($tenants as $tenant) {
            $this->line("  ⏳ Backup: {$tenant->name} ({$tenant->id})...");
            $path = $backup->backupTenant($tenant);
            if ($path) {
                $this->line("  ✅ → " . basename($path) . ' (' . round(filesize($path) / 1024, 1) . ' KB)');
                $ok++;
            } else {
                $this->error("  ❌ Failed: {$tenant->name}");
                $err++;
            }
        }

        $this->info("Done: {$ok} OK, {$err} failed.");
        return $err > 0 ? self::FAILURE : self::SUCCESS;
    }
}
