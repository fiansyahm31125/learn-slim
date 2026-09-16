<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;

class ProductService
{
    public function __construct(private ProductRepository $pr) {}

    public function getJson($id)
    {
        if ($id !== null) {
            // Ada ID → filter berdasarkan ID
            $products = $this->pr->findBy([
                'id' => (int) $id
            ]);
        } else {
            // Tidak ada ID → ambil semua
            $products = $this->pr->findAll();
        }
        $data = array_map(fn(Product $p) => $p->toArray(), $products);
        return $data;
    }

    public function find($id)
    {
        return $this->pr->find($id);
    }

    public function findAll()
    {
        return  $this->pr->findAll();
    }

    public function create(string $name, int $price, int $stock)
    {
        return $this->pr->create($name, (int) $price, (int) $stock);
    }

    public function update($id, $data): ?Product
    {
        $product = $this->pr->find($id);
        if (!$product) {
            return null;
        }
        if (isset($data['name'])) {
            $product->setName($data['name']);
        }
        if (isset($data['price'])) {
            $product->setPrice((int) $data['price']);
        }
        if (isset($data['stock'])) {
            $product->setStock((int) $data['stock']);
        }
        $this->pr->save($product);
        return $product;
    }

    public function delete($id): ?Product
    {
        $product = $this->pr->find($id);
        if (!$product) {
            return null;
        }
        $this->pr->delete($product);
        return $product;
    }
}
