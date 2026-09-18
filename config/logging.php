<?php

declare(strict_types=1);

use App\Support\AppLogger;

/**
 * Inisialisasi logger file dari ENV.
 *
 * ENV:
 * - LOG_DIR     (default: <project>/var/log)
 * - LOG_LEVEL   (debug|info|warning|error, default: debug bila APP_DEBUG=true, else info)
 * - LOG_REQUESTS (1|true|yes|on = catat tiap request, default: true)
 */
function initLogger(): AppLogger
{
    $projectRoot = dirname(__DIR__);
    $logDir = $_ENV['LOG_DIR'] ?? $_SERVER['LOG_DIR'] ?? getenv('LOG_DIR') ?: $projectRoot . '/var/log';

    $rawLevel = $_ENV['LOG_LEVEL'] ?? $_SERVER['LOG_LEVEL'] ?? getenv('LOG_LEVEL') ?: '';
    if (trim((string) $rawLevel) === '') {
        $debug = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'false';
        $rawLevel = in_array(strtolower(trim((string) $debug)), ['1', 'true', 'yes', 'on'], true)
            ? 'debug'
            : 'info';
    }

    return AppLogger::init((string) $logDir, (string) $rawLevel);
}

function shouldLogRequests(): bool
{
    $raw = $_ENV['LOG_REQUESTS'] ?? $_SERVER['LOG_REQUESTS'] ?? getenv('LOG_REQUESTS') ?: 'true';

    return in_array(strtolower(trim((string) $raw)), ['1', 'true', 'yes', 'on'], true);
}
