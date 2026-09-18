<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Konfigurasi + helper CORS.
 *
 * ENV:
 * - CORS_ALLOWED_ORIGINS  (default: *). Daftar dipisah koma, mis:
 *   "https://app.example.com,https://admin.example.com". Spesial: "*" = semua origin.
 * - CORS_ALLOWED_METHODS  (default: GET,POST,PUT,DELETE,PATCH,OPTIONS)
 * - CORS_ALLOWED_HEADERS  (default: Content-Type,Authorization,Accept,X-Requested-With).
 *   "Access-Control-Request-Headers" dari preflight selalu diizinkan bila tercantum
 *   di daftar ini; bila "*" maka echo balik header yang diminta.
 * - CORS_ALLOW_CREDENTIALS (default: false). Bila true, origin TIDAK boleh "*":
 *   hanya origin yang ada di allowlist yang di-echo balik.
 * - CORS_MAX_AGE (detik cache preflight, default: 86400).
 *
 * Aturan keamanan:
 * - Tidak pernah meng-echo Origin sembarangan saat credentials=true.
 * - Selalu kirim "Vary: Origin" agar cache tidak tertukar antar origin.
 */

function envStr(string $key, string $default = ''): string
{
    $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($v === false || $v === null) {
        return $default;
    }

    return trim((string) $v);
}

/** @return array{origins:list<string>, methods:string, headers:string, credentials:bool, maxAge:string} */
function getCorsConfig(): array
{
    $origins = array_values(array_filter(array_map(
        static fn(string $o): string => trim($o),
        explode(',', envStr('CORS_ALLOWED_ORIGINS', '*'))
    )));

    if ($origins === []) {
        $origins = ['*'];
    }

    $credentials = in_array(strtolower(envStr('CORS_ALLOW_CREDENTIALS', 'false')), ['1', 'true', 'yes', 'on'], true);

    return [
        'origins' => $origins,
        'methods' => envStr('CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,PATCH,OPTIONS'),
        'headers' => envStr('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,Accept,X-Requested-With'),
        'credentials' => $credentials,
        'maxAge' => envStr('CORS_MAX_AGE', '86400'),
    ];
}

/**
 * Tentukan nilai Access-Control-Allow-Origin untuk request ini.
 * Return null bila origin tidak diizinkan (header tidak dikirim).
 */
function resolveCorsOrigin(ServerRequestInterface $request, ?array $config = null): ?string
{
    $config ??= getCorsConfig();
    $origin = trim($request->getHeaderLine('Origin'));
    if ($origin === '') {
        return null; // Bukan request CORS (mis. curl, mobile, server-to-server).
    }

    $origins = $config['origins'];

    // Wildcard tanpa credentials -> "*".
    if (in_array('*', $origins, true) && !$config['credentials']) {
        return '*';
    }

    // Allowlist eksplisit (case-insensitive, tanpa trailing slash).
    $norm = strtolower(rtrim($origin, '/'));
    foreach ($origins as $allowed) {
        if ($allowed === '*') {
            continue; // Dengan credentials, "*" diabaikan demi keamanan.
        }
        if (strtolower(rtrim($allowed, '/')) === $norm) {
            return $origin; // Echo balik origin asli (bukan versi normalisasi).
        }
    }

    return null;
}

/**
 * Tambahkan header CORS ke response. Idempoten, tidak pernah melempar.
 */
function addCorsHeaders(ResponseInterface $response, ServerRequestInterface $request, ?array $config = null): ResponseInterface
{
    try {
        $config ??= getCorsConfig();
        $origin = resolveCorsOrigin($request, $config);
        if ($origin === null) {
            return $response;
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Methods', $config['methods'])
            ->withHeader('Access-Control-Allow-Headers', $config['headers'])
            ->withHeader('Access-Control-Max-Age', $config['maxAge']);

        if ($config['credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    } catch (\Throwable) {
        return $response;
    }
}
