<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use DI\Container;
use Doctrine\ORM\EntityManager;
use App\Entity\Product;

class ProductController
{

    public function __construct(private EntityManager $em) {}

    public function index(Request $request, Response $response)
    {
        $products = $this->em->getRepository(Product::class)->findAll();
        $data = array_map(fn(Product $p) => $p->toArray(), $products);
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function show(Request $request, Response $response, array $args)
    {
        $product = $this->em->find(Product::class, (int) $args['id']);

        if (!$product) {
            $response->getBody()->write(json_encode(['error' => 'Produk tidak ditemukan']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($product->toArray(), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function page($request, $response)
    {
        $products = $this->em->getRepository(Product::class)->findAll();

        $view = Twig::fromRequest($request);
        return $view->render($response, 'products.html.twig', [
            'products' => array_map(fn(Product $p) => $p->toArray(), $products),
        ]);
    }
}
