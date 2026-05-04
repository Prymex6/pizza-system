<?php

/**
 * Deploy worker — uruchamiany jako osobny proces PHP CLI
 * NIE przez Apache — może działać dowolnie długo
 */

$appDir  = dirname(__DIR__);
$logFile = $appDir . '/storage/logs/deploy.log';

function logLine(string $line, string $file): void {
    file_put_contents($file, $line . PHP_EOL, FILE_APPEND);
}

// ── Wyczyść OPcache (jeśli dostępny w CLI) ────────────────────────
if (function_exists('opcache_reset')) {
    opcache_reset();
}

$php = PHP_BINARY;

// ── Szukaj git.exe ────────────────────────────────────────────────
$gitCandidates = [
    'D:/Programy/Git/cmd/git.exe',
    'D:/Programy/Git/bin/git.exe',
    'C:/Program Files/Git/cmd/git.exe',
    'C:/Program Files/Git/bin/git.exe',
    'C:/Program Files (x86)/Git/cmd/git.exe',
    'C:/Program Files (x86)/Git/bin/git.exe',
];
$git = null;
foreach ($gitCandidates as $candidate) {
    if (file_exists($candidate)) {
        $git = $candidate;
        break;
    }
}
if ($git === null) {
    logLine('[' . date('Y-m-d H:i:s') . '] ERROR: git.exe not found', $logFile);
    exit(1);
}

$composer = 'C:/ProgramData/ComposerSetup/bin/composer.bat';
if (!file_exists($composer)) {
    $composer = '"' . $php . '" "' . $appDir . '/composer.phar"';
}

$gitSafe = '"' . $git . '" -c safe.directory="' . $appDir . '"';

// ── Szukaj npm ────────────────────────────────────────────────────
$npmCandidates = [
    'D:/Programy/Node.js/npm.cmd',
    'C:/Program Files/nodejs/npm.cmd',
    'C:/Program Files (x86)/nodejs/npm.cmd',
    'D:/Programy/nodejs/npm.cmd',
];
$npm = null;
foreach ($npmCandidates as $candidate) {
    if (file_exists($candidate)) {
        $npm = $candidate;
        break;
    }
}

// ── Komendy ───────────────────────────────────────────────────────
$commands = [
    $gitSafe . ' -C "' . $appDir . '" fetch origin',
    $gitSafe . ' -C "' . $appDir . '" reset --hard origin/main',
    $composer . ' install --working-dir="' . $appDir . '" --no-dev --optimize-autoloader --no-interaction',
    '"' . $php . '" "' . $appDir . '/artisan" config:cache',
    '"' . $php . '" "' . $appDir . '/artisan" route:clear',
    '"' . $php . '" "' . $appDir . '/artisan" view:cache',
    '"' . $php . '" "' . $appDir . '/artisan" migrate --force',
    '"' . $php . '" "' . $appDir . '/artisan" queue:restart',
    '"' . $php . '" "' . $appDir . '/artisan" tenant:fix-legal-placeholders',
    '"' . $php . '" "' . $appDir . '/artisan" tenant:storage-link',
];

if ($npm) {
    array_splice($commands, 3, 0, [
        '"' . $npm . '" install --prefix "' . $appDir . '"',
        '"' . $npm . '" run build --prefix "' . $appDir . '"',
    ]);
} else {
    logLine('[' . date('Y-m-d H:i:s') . '] WARN: npm not found — skipping frontend build', $logFile);
}

// ── Reset OPcache Apache (CLI opcache_reset nie czyści procesu Apache) ────
// Wywoływany PO wszystkich komendach (patrz poniżej pętla)
function resetApacheOpcache(string $logFile): void {
    $url = 'http://127.0.0.1/opcache-reset.php';
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $result = @file_get_contents($url, false, $ctx);
    logLine('[OPcache] ' . ($result !== false ? trim($result) : 'request failed'), $logFile);
}

// ── Wykonanie ─────────────────────────────────────────────────────
logLine('[' . date('Y-m-d H:i:s') . '] Deploy started (git: ' . $git . ')', $logFile);
$error = false;

foreach ($commands as $cmd) {
    logLine('$ ' . $cmd, $logFile);
    $output     = [];
    $returnCode = 0;
    exec($cmd . ' 2>&1', $output, $returnCode);
    logLine(implode(PHP_EOL, $output), $logFile);
    if ($returnCode !== 0) {
        logLine('ERROR (exit ' . $returnCode . ')', $logFile);
        $error = true;
        break;
    }
    logLine('OK', $logFile);
}

if (!$error) {
    resetApacheOpcache($logFile);
}

logLine(
    $error
        ? '[' . date('Y-m-d H:i:s') . '] Deploy FAILED'
        : '[' . date('Y-m-d H:i:s') . '] Deploy OK',
    $logFile
);
