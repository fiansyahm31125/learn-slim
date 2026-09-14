<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ProductController
{
    public function __construct(
        private EntityManager $em
    ) {}

    /**
     * GET /products — daftar semua produk (JSON)
     */
    public function index(Request $request, Response $response): Response
    {
        $products = $this->em->getRepository(Product::class)->findAll();

        $data = array_map(fn(Product $p) => $p->toArray(), $products);

        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * GET /products/{id} — 1 produk by id (JSON)
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $product = $this->em->find(Product::class, (int) $args['id']);

        if (!$product) {
            $response->getBody()->write(json_encode(['error' => 'Produk tidak ditemukan']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($product->toArray(), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * GET /products-page — tampilkan produk via Twig (HTML)
     */
    public function page(Request $request, Response $response): Response
    {
        $products = $this->em->getRepository(Product::class)->findAll();

        $view = Twig::fromRequest($request);
        return $view->render($response, 'products.html.twig', [
            'products' => array_map(fn(Product $p) => $p->toArray(), $products),
        ]);
    }
}
