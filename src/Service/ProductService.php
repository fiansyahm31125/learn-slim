<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Validation\ProductValidator;

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

    /**
     * Add product. Validasi full (name, price, stock wajib).
     *
     * @param array<string, mixed> $data
     * @throws \App\Exception\ValidationException
     */
    public function create(array $data): Product
    {
        $clean = ProductValidator::validateForCreate($data);
        return $this->pr->create($clean['name'], $clean['price'], $clean['stock']);
    }

    /**
     * Edit product. Validasi partial (hanya field yang dikirim).
     *
     * @param array<string, mixed> $data
     * @throws \App\Exception\ValidationException
     */
    public function update($id, $data): ?Product
    {
        $product = $this->pr->find($id);
        if (!$product) {
            return null;
        }
        $clean = ProductValidator::validateForUpdate((array) $data);
        if (isset($clean['name'])) {
            $product->setName($clean['name']);
        }
        if (isset($clean['price'])) {
            $product->setPrice($clean['price']);
        }
        if (isset($clean['stock'])) {
            $product->setStock($clean['stock']);
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
