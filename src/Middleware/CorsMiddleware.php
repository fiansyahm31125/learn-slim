<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Middleware CORS.
 *
 * - Preflight OPTIONS: short-circuit 204 + header CORS tanpa menyentuh route.
 *   (Dipasang di $app->add() sehingga berjalan SEBELUM routing → tidak 404.)
 * - Request aktual: teruskan ke handler lalu tempel header CORS ke response.
 * - Response error dari ErrorMiddleware juga ikut ditempel karena middleware
 *   ini berada LEBIH LUAR dari ErrorMiddleware (ditambahkan setelahnya).
 * - Tidak pernah memecahkan request (try/catch di sekeliling semuanya).
 */
class CorsMiddleware implements MiddlewareInterface
{
    /** @param array{origins:list<string>, methods:string, headers:string, credentials:bool, maxAge:string}|null $config */
    public function __construct(private ?array $config = null) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $config = $this->config ?? getCorsConfig();
        } catch (\Throwable) {
            return $handler->handle($request);
        }

        // Preflight: browser mengecek izin sebelum request asli.
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            try {
                $response = (new Response())->withStatus(204);
                $response = addCorsHeaders($response, $request, $config);
                // Echo balik header yang diminta bila konfigurasi memakai wildcard.
                if (trim($config['headers']) === '*') {
                    $asked = trim($request->getHeaderLine('Access-Control-Request-Headers'));
                    if ($asked !== '') {
                        $response = $response->withHeader('Access-Control-Allow-Headers', $asked);
                    }
                }

                return $response;
            } catch (\Throwable) {
                return (new Response())->withStatus(204);
            }
        }

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            // Biarkan ErrorMiddleware yang merender; ia ikut menempel header CORS
            // via AppError (lihat helper/apperror.php). Lempar ulang agar tidak
            // menelan error.
            throw $e;
        }

        return addCorsHeaders($response, $request, $config);
    }
}
