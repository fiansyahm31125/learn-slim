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
use App\Repository\ProductRepository;
use App\Service\ProductService;

class ProductController
{

    public function __construct(private EntityManager $em, private ProductRepository $pr, private ProductService $ps) {}

    public function index(Request $request, Response $response, array $args)
    {
        $id = $args['id'] ?? null;
        $data = $this->ps->getJson($id);
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function show(Request $request, Response $response, array $args)
    {
        $id = $args['id'];
        $product = $this->ps->find($id);
        if (!$product) {
            $response->getBody()->write(json_encode(['error' => 'Produk tidak ditemukan']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode($product->toArray(), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // public function detail(Request $request, Response $response, string $id)
    // {
    //     $product = $this->em->find(Product::class, (int) $id);

    //     if (!$product) {
    //         $response->getBody()->write(json_encode(['error' => 'Produk tidak ditemukan']));
    //         return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    //     }

    //     $response->getBody()->write(json_encode($product->toArray(), JSON_PRETTY_PRINT));
    //     return $response->withHeader('Content-Type', 'application/json');
    // }

    public function page($request, $response)
    {
        $products = $this->ps->findAll();
        $view = Twig::fromRequest($request);
        return $view->render($response, 'products.html.twig', [
            'products' => array_map(fn(Product $p) => $p->toArray(), $products),
        ]);
    }

    public function crud($request, $response)
    {
        $products = $this->ps->findAll();

        $view = Twig::fromRequest($request);
        return $view->render($response, 'crud.html.twig', [
            'products' => array_map(fn(Product $p) => $p->toArray(), $products),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $name = $data['name'] ?? null;
        $price = $data['price'] ?? null;
        $stock = $data['stock'] ?? null;

        $product = $this->ps->create($name, (int) $price, (int) $stock);
        $response->getBody()->write(json_encode($product->toArray(), JSON_PRETTY_PRINT));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $data = $request->getParsedBody() ?? [];
        $product = $this->ps->update($id, $data);
        if (!$product) {
            $response->getBody()->write(json_encode(['error' => 'Produk tidak ditemukan']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode($product->toArray(), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $product = $this->ps->delete($id);
        if (!$product) {
            $response->getBody()->write(json_encode(['error' => 'Produk tidak ditemukan']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['message' => 'Produk berhasil dihapus', 'id' => $id], JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
