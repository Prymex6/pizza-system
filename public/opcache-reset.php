<?php

/**
 * OPcache reset — wywoływany tylko z localhost przez deploy-worker.
 * Czyści OPcache Apache (CLI opcache_reset() nie dotyczy procesu Apache).
 */

$allowedIps = ['127.0.0.1', '::1'];
$remoteIp   = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($remoteIp, $allowedIps, true)) {
    http_response_code(403);
    exit('Forbidden');
}

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OPcache reset OK';
} else {
    echo 'OPcache not available';
}
