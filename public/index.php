<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Create Twig
$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

$app->get('/', function ($request, $response) {
    $view = Twig::fromRequest($request);

    return $view->render($response, 'home.html.twig', [
        'name' => 'John',
    ]);
});

$app->run();
