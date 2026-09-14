<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use DI\Container;
use App\Controller\ProductController;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/doctrine.php';

// Create Container using PHP-DI
$container = new Container();

// Daftarkan Doctrine EntityManager di container
$container->set(Doctrine\ORM\EntityManager::class, function () {
    return getEntityManager();
});

// Set container to create App with on AppFactory
AppFactory::setContainer($container);

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

// Contoh Doctrine ORM sederhana: daftar semua produk (JSON)
// $app->get('/products', function (Request $request, Response $response) {
//     /** @var Doctrine\ORM\EntityManager $em */
//     $em = $this->get(Doctrine\ORM\EntityManager::class);
//     $products = $em->getRepository(App\Entity\Product::class)->findAll();

//     $data = array_map(fn(App\Entity\Product $p) => $p->toArray(), $products);

//     $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
//     $response->withHeader('Content-Type', 'application/json');
// });

$app->get('/products', [ProductController::class, 'index']);

// Contoh Doctrine ORM: 1 produk by id (JSON)
$app->get('/products/{id}', [ProductController::class, 'show']);

// Contoh Doctrine ORM: tampilkan produk via Twig (HTML)
$app->get('/products-page', [ProductController::class, 'page']);

$app->run();
