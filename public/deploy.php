<?php

/**
 * GitHub Webhook — auto-deploy
 * URL: https://roveto.pl/deploy.php
 */

$appDir = dirname(__DIR__);
$logFile = $appDir . '/storage/logs/deploy.log';

// ── Wczytaj DEPLOY_SECRET z .env ──────────────────────────────────
$secret = '';
$envFile = $appDir . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), 'DEPLOY_SECRET=')) {
            $secret = trim(substr($line, strpos($line, '=') + 1), " \t\"'");
            break;
        }
    }
}

// ── Weryfikacja tokenu HMAC ────────────────────────────────────────
$payload = file_get_contents('php://input');

if ($secret !== '') {
    $sig = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    $hub = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    if (! hash_equals($sig, $hub)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// ── Tylko branch main ─────────────────────────────────────────────
$data   = json_decode($payload, true);
$branch = $data['ref'] ?? '';
if ($branch !== '' && ! in_array($branch, ['refs/heads/main', 'refs/heads/master'], true)) {
    http_response_code(200);
    exit('Ignored branch: ' . $branch);
}

// ── Uruchom worker jako osobny proces PHP (niezależny od Apache) ──
$php    = 'D:/Programy/Xampp/php/php.exe';
$worker = $appDir . '/storage/deploy-worker.php';

// Windows: cmd /c start /B uruchamia proces w tle przez cmd.exe
$cmd = 'cmd /c start /B "" "' . $php . '" "' . $worker . '" > nul 2>&1';
pclose(popen($cmd, 'r'));

file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] Webhook received — worker started' . PHP_EOL, FILE_APPEND);

http_response_code(202);
echo 'OK';
