<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\ORM\EntityManager;

class CallbackController
{
    public function __construct(
        private EntityManager $em
    ) {}

    public function closureBinding(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $name = $args['name'] ?? '';

        return $response
            ->withHeader(
                'Set-Cookie',
                'name=' . rawurlencode($name) . '; Max-Age=604800; Path=/; HttpOnly'
            );
    }
}
