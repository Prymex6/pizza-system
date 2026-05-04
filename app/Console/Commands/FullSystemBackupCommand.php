<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class FullSystemBackupCommand extends Command
{
    protected $signature   = 'backup:full';
    protected $description = 'Full system backup: landlord DB + all tenant DBs + manifest';

    public function handle(BackupService $backup): int
    {
        $this->info('🗄  Starting full system backup...');

        $results = $backup->backupAll();

        $ok  = count(array_filter($results));
        $all = count($results);

        foreach ($results as $key => $path) {
            if ($path) {
                $this->line("  ✅ {$key} → " . basename($path) . ' (' . round(filesize($path) / 1024, 1) . ' KB)');
            } else {
                $this->error("  ❌ {$key} — failed");
            }
        }

        $this->info("Backup complete: {$ok}/{$all} databases.");
        return $ok === $all ? self::SUCCESS : self::FAILURE;
    }
}
