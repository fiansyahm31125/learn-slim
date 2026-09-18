<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use DI\Container;
use Dotenv\Dotenv;
use App\Controller\ProductController;
use App\Controller\CallbackController;
use App\Controller\McpController;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Routing\RouteCollectorProxy;


require __DIR__ . '/../vendor/autoload.php';

// Load variabel environment dari .env (diabaikan jika file tidak ada,
// mis. production yang memakai environment variable asli)
Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/middleware.php';
require __DIR__ . '/../config/middlewarePost.php';

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


$app->post('/mcp', [McpController::class, 'handle']);


$app->get('/', function ($request, $response) {
    $response->getBody()->write('OK');

    return $response
        ->withHeader('Content-Type', 'text/plain');
});

$app->post('/', function ($request, $response) {
    return $response
        ->withStatus(401)
        ->withHeader('Content-Type', 'application/json');
});

// $app->get('/', function ($request, $response) {
//     $view = Twig::fromRequest($request);

//     return $view->render($response, 'home.html.twig', [
//         'name' => 'John',
//     ]);
// });

// Contoh Doctrine ORM sederhana: daftar semua produk (JSON)
// $app->get('/products', function (Request $request, Response $response) {
//     /** @var Doctrine\ORM\EntityManager $em */
//     $em = $this->get(Doctrine\ORM\EntityManager::class);
//     $products = $em->getRepository(App\Entity\Product::class)->findAll();

//     $data = array_map(fn(App\Entity\Product $p) => $p->toArray(), $products);

//     $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
//     $response->withHeader('Content-Type', 'application/json');
// });

$app->get('/mcp', [
    ProductController::class,
    'index'
]);

$app->group('/products', function (RouteCollectorProxy $group) {

    // GET /products
    $group->get('', [
        ProductController::class,
        'index'
    ]);

    // POST /products/create
    $group->post('/create', [
        ProductController::class,
        'create'
    ]);

    // GET /products/show/1
    $group->get('/show/{id}', [
        ProductController::class,
        'show'
    ]);

    // GET /products/page
    $group->get('/page', [
        ProductController::class,
        'page'
    ])->setName('productpage');

    // GET /products/crud
    $group->get('/crud', [
        ProductController::class,
        'crud'
    ])->setName('productcrud');

    // DELETE /products/1
    $group->delete('/{id}', [
        ProductController::class,
        'delete'
    ])->setName('product-delete');

    // PUT /products/1
    $group->put('/{id}', [
        ProductController::class,
        'update'
    ]);
});
// ->add(
//     new AuthMiddleware(
//         $app->getResponseFactory()
//     )
// );



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


// PSR-7 (Can't change except using special methos )
$app->get('/foo', function (Request $request, Response $response, array $args) {
    $payload = json_encode(['hello' => 'world'], JSON_PRETTY_PRINT);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
});

$app->run();
