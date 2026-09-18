<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\AppLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Mencatat aktivitas tiap request: method, URI (token disamarkan),
 * status response, durasi, IP dan User-Agent.
 *
 * - 2xx/3xx -> info, 4xx -> warning, 5xx -> error.
 * - Exception yang lolos tetap dicatat lalu dilempar ulang agar
 *   error handler terpusat tetap merender response aman.
 * - Tidak pernah memecahkan request (try/catch di sekeliling logging).
 */
class RequestLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AppLogger $logger,
        private bool $enabled = true
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        $start = microtime(true);
        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        try {
            $response = $handler->handle($request);
            $this->log($request, $response->getStatusCode(), $start);

            return $response;
        } catch (\Throwable $e) {
            // Status belum diketahui (error handler akan menentukannya);
            // catat sebagai 500 agar jejak exception tidak hilang bila
            // error handler gagal. Error handler juga mencatat via AppError.
            $this->log($request, 500, $start, ['unhandled' => $e::class]);

            throw $e;
        }
    }

    private function log(
        ServerRequestInterface $request,
        int $status,
        float $start,
        array $extra = []
    ): void {
        try {
            $params = $request->getServerParams();
            $this->logger->logRequest(
                $request->getMethod(),
                (string) $request->getUri(),
                $status,
                (microtime(true) - $start) * 1000,
                array_merge([
                    'ip' => $params['REMOTE_ADDR'] ?? null,
                    'ua' => substr($request->getHeaderLine('User-Agent'), 0, 200) ?: null,
                ], $extra)
            );
        } catch (\Throwable) {
            // Diam: logging tidak boleh menggagalkan request.
        }
    }
}
