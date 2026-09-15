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

    public function index(Request $request, Response $response, array $args)
    {
        $id = $args['id'] ?? null;

        if ($id !== null) {
            // Ada ID → filter berdasarkan ID
            $products = $this->em->getRepository(Product::class)->findBy([
                'id' => (int) $id
            ]);
        } else {
            // Tidak ada ID → ambil semua
            $products = $this->em->getRepository(Product::class)->findAll();
        }
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

    public function detail(Request $request, Response $response, string $id)
    {
        $product = $this->em->find(Product::class, (int) $id);

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

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $name = $data['name'] ?? null;
        $price = $data['price'] ?? null;
        $stock = $data['stock'] ?? null;

        // $errors = [];
        // if ($name === '') {
        //     $errors['name'] = 'Nama wajib diisi.';
        // }
        // if ($price === null || $price === '' || filter_var($price, FILTER_VALIDATE_INT) === false || (int) $price < 0) {
        //     $errors['price'] = 'Price wajib berupa integer >= 0.';
        // }
        // if ($stock === null || $stock === '' || filter_var($stock, FILTER_VALIDATE_INT) === false || (int) $stock < 0) {
        //     $errors['stock'] = 'Stock wajib berupa integer >= 0.';
        // }

        // if ($errors !== []) {
        //     $response->getBody()->write(json_encode(['errors' => $errors], JSON_PRETTY_PRINT));
        //     return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        // }

        $product = new Product($name, (int) $price, (int) $stock);
        $this->em->persist($product);
        $this->em->flush();

        $response->getBody()->write(json_encode($product->toArray(), JSON_PRETTY_PRINT));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $product = $this->em->find(Product::class, (int) $args['id']);

        if (!$product) {
            $response->getBody()->write(json_encode(['error' => 'Produk tidak ditemukan']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $data = $request->getParsedBody() ?? [];

        if (isset($data['name'])) {
            $product->setName($data['name']);
        }
        if (isset($data['price'])) {
            $product->setPrice((int) $data['price']);
        }
        if (isset($data['stock'])) {
            $product->setStock((int) $data['stock']);
        }

        $this->em->flush();

        $response->getBody()->write(json_encode($product->toArray(), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $product = $this->em->find(Product::class, (int) $args['id']);

        if (!$product) {
            $response->getBody()->write(json_encode(['error' => 'Produk tidak ditemukan']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $this->em->remove($product);
        $this->em->flush();

        $response->getBody()->write(json_encode(['message' => 'Produk berhasil dihapus', 'id' => (int) $args['id']], JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
