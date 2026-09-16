<?php

namespace App\Repository;

use Doctrine\ORM\EntityManager;
use App\Entity\Product;

class ProductRepository
{
    public function __construct(private EntityManager $em) {}

    public function find(int $id)
    {
        return  $this->em->find(Product::class, $id);
    }

    public function findBy(array $params)
    {
        return  $this->em->getRepository(Product::class)->findBy($params);
    }

    public function findAll()
    {
        return  $this->em->getRepository(Product::class)->findAll();
    }

    public function create(string $name, int $price, int $stock): Product
    {
        $product = new Product($name, (int) $price, (int) $stock);
        $this->em->persist($product);
        $this->em->flush();
        return $product;
    }

    public function save(Product $product): void
    {
        $this->em->persist($product);
        $this->em->flush();
    }
    public function delete(Product $product): void
    {
        $this->em->remove($product);
        $this->em->flush();
    }
}
