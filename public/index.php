<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use DI\Container;
use App\Controller\ProductController;
use App\Controller\CallbackController;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Routing\RouteCollectorProxy;

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

// RequestResponseArgs code, membuat  Route strategies mati
// $routeCollector = $app->getRouteCollector();
// $routeCollector->setDefaultInvocationStrategy(
//     new RequestResponseArgs()
// );

$app->addRoutingMiddleware();

$routeParser = $app->getRouteCollector()->getRouteParser();

$displayErrorDetails = true;
$logErrors = true;
$logErrorDetails = true;

$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);

$app->addBodyParsingMiddleware();

$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);

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

$app->post('/products-create', [ProductController::class, 'create']);

// Contoh Doctrine ORM: 1 produk by id (JSON)
// Route strategies
$app->get('/products/show/{id}', [ProductController::class, 'show']);
// RequestResponseArgs
$app->get('/products/detail/{id}', [ProductController::class, 'detail']);

// Contoh Doctrine ORM: tampilkan produk via Twig (HTML)
// Route names
$app->get('/products-page', [ProductController::class, 'page'])->setName('productpage');
$app->redirect('/halaman-product', $routeParser->urlFor('productpage'));

$app->delete('/products/{id}', [ProductController::class, 'delete']);

$app->put('/products/{id}', [ProductController::class, 'update']);

$app->get('/callbackbinding/{name}', [CallbackController::class, 'closureBinding']);

// Redirect helper
$app->redirect('/items', '/products', 301);

// Route groups
$app->group('/users/{id}', function (RouteCollectorProxy $group) {
    $group->get('/billing', function ($request, $response, array $args) {
        $id = (int) $args['id'];
        $data = [
            'id' => $id
        ];

        $response->getBody()->write(
            json_encode($data, JSON_PRETTY_PRINT)
        );

        return $response
            ->withHeader('Content-Type', 'application/json');
    });


    $group->get('/product', [ProductController::class, 'index']);
});

$app->run();
