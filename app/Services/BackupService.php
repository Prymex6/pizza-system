<?php

namespace App\Services;

use App\Models\Landlord\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackupService
{
    private string $mysqldump;
    private TenantStorageService $storage;

    public function __construct(TenantStorageService $storage)
    {
        $this->mysqldump = config('app.mysqldump_path', 'mysqldump');
        $this->storage   = $storage;
    }

    // ── Single tenant backup ───────────────────────────────────────────────

    public function backupTenant(Tenant $tenant): ?string
    {
        try {
            tenancy()->initialize($tenant);
            $dbName = DB::connection()->getDatabaseName();
            tenancy()->end();
        } catch (\Throwable $e) {
            Log::error("[Backup] Cannot get DB name for tenant {$tenant->id}: {$e->getMessage()}");
            if (tenancy()->initialized) tenancy()->end();
            return null;
        }

        $yearMonth = date('Y-m');
        $dir       = $this->storage->backupsPath($tenant->id, $yearMonth);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql.gz';
        $filepath = "{$dir}/{$filename}";

        $success = $this->runMysqldump($dbName, $filepath);

        if ($success) {
            Log::channel('single')->info("[Backup] Tenant {$tenant->name} ({$tenant->id}) → {$filepath} (" . $this->humanSize(filesize($filepath)) . ")");
            $this->pruneOldBackups($this->storage->backupsPath($tenant->id), days: 30);
            return $filepath;
        }

        return null;
    }

    // ── Full system backup (all tenants + landlord) ────────────────────────

    public function backupAll(): array
    {
        $results = [];

        // 1. Landlord DB
        $results['landlord'] = $this->backupDatabase(
            config('database.connections.landlord.database', config('database.connections.' . config('database.default') . '.database')),
            storage_path('backups/full/' . date('Y-m')),
            'landlord_' . date('Y-m-d_H') . '.sql.gz'
        );

        // 2. Each tenant DB
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            $path = $this->backupTenant($tenant);
            $results["tenant_{$tenant->id}"] = $path;
        }

        // 3. Write manifest
        $manifestDir = storage_path('backups/full/' . date('Y-m'));
        if (!is_dir($manifestDir)) mkdir($manifestDir, 0755, true);
        file_put_contents(
            "{$manifestDir}/manifest_" . date('Y-m-d_H') . '.json',
            json_encode([
                'created_at' => now()->toIso8601String(),
                'files'      => array_filter($results),
            ], JSON_PRETTY_PRINT)
        );

        $count = count(array_filter($results));
        Log::channel('single')->info("[Backup] Full backup done: {$count}/" . count($results) . " databases.");

        return $results;
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function backupDatabase(string $dbName, string $dir, string $filename): ?string
    {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filepath = "{$dir}/{$filename}";
        $success  = $this->runMysqldump($dbName, $filepath);
        return $success ? $filepath : null;
    }

    private function runMysqldump(string $dbName, string $destGz): bool
    {
        $host     = config('database.connections.central.host', '127.0.0.1');
        $port     = config('database.connections.central.port', '3306');
        $user     = config('database.connections.central.username', 'root');
        $pass     = config('database.connections.central.password', '');
        $dump     = $this->mysqldump;

        $passArg  = $pass !== '' ? '-p' . escapeshellarg($pass) : '';
        // On Windows escapeshellarg adds quotes — use the raw value with no spaces
        $passFlag = $pass !== '' ? "-p\"{$pass}\"" : '';

        // Build command — pipe through PHP gzip since the system `gzip` may not exist on Windows
        $tmpSql = $destGz . '.tmp.sql';

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "\"{$dump}\" -h{$host} -P{$port} -u{$user} {$passFlag} --single-transaction --routines --triggers --hex-blob {$dbName} > \"{$tmpSql}\" 2>&1";
        } else {
            $cmd = "{$dump} -h{$host} -P{$port} -u{$user} {$passArg} --single-transaction --routines --triggers --hex-blob {$dbName} 2>/dev/null | gzip -c > \"{$destGz}\"";
        }

        if (PHP_OS_FAMILY === 'Windows') {
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($tmpSql)) {
                $errDetail = file_exists($tmpSql) ? trim(file_get_contents($tmpSql)) : implode("\n", $output);
                @unlink($tmpSql);
                Log::error("[Backup] mysqldump failed for {$dbName}: " . $errDetail);
                return false;
            }

            // Compress with PHP gzip
            $sql  = file_get_contents($tmpSql);
            $gz   = gzencode($sql, 6);
            unlink($tmpSql);

            if ($gz === false) {
                Log::error("[Backup] gzencode failed for {$dbName}");
                return false;
            }

            file_put_contents($destGz, $gz);
        } else {
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                Log::error("[Backup] mysqldump failed for {$dbName}: " . implode("\n", $output));
                return false;
            }
        }

        return file_exists($destGz) && filesize($destGz) > 0;
    }

    /**
     * Delete .sql.gz backups older than $days days inside the given root directory (recursive).
     */
    private function pruneOldBackups(string $rootDir, int $days = 30): void
    {
        $cutoff = time() - ($days * 86400);
        $it     = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if ($file->isFile() && $file->getExtension() === 'gz' && $file->getMTime() < $cutoff) {
                unlink($file->getPathname());
            }
        }
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
