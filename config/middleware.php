<?php

use App\Helper\AppError;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

require __DIR__ . '/../helper/apperror.php';

class AuthMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    // ResponseInterface adalah kode yang mengikuti psr-17 yang sudah ada di library yang dipanggil oleh slim
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $validToken = $_ENV['AUTH_TOKEN'] ?? $_SERVER['AUTH_TOKEN'] ?? getenv('AUTH_TOKEN') ?: '';

        if ($validToken === '') {
            return $this->jsonError('AUTH_TOKEN belum dikonfigurasi di .env', 500);
        }

        $params = $request->getQueryParams();
        if (!array_key_exists('token', $params) || $params['token'] === '') {
            // return $this->jsonError('Unauthorized', 401);
            // $response = new AppError();
            // return $response->process('Unauthorized', 401, $request);
            return AppError::make('Unauthorized', 401, $request);
        }

        if (!hash_equals($validToken, (string) $params['token'])) {
            // return $this->jsonError('Forbidden', 403);
            return $this->forbidden();
        }

        return $handler->handle($request);
    }

    private function jsonError(string $message, int $status): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
            'status' => $status,
        ]));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    private function forbidden(): ResponseInterface
    {
        return $this->jsonError('Forbidden', 403);
    }
}
