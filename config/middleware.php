<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $validToken = $_ENV['AUTH_TOKEN'] ?? $_SERVER['AUTH_TOKEN'] ?? getenv('AUTH_TOKEN') ?: '';

        if ($validToken === '') {
            return $this->jsonError('AUTH_TOKEN belum dikonfigurasi di .env', 500);
        }

        $params = $request->getQueryParams();
        if (!array_key_exists('token', $params) || $params['token'] === '') {
            return $this->jsonError('Unauthorized', 401);
        }

        if (!hash_equals($validToken, (string) $params['token'])) {
            return $this->jsonError('Forbidden', 403);
        }

        return $handler->handle($request);
    }

    private function jsonError(string $message, int $status): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
