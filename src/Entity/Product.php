<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int|null $id;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $price;

    #[ORM\Column(type: 'integer')]
    private int $stock;

    public function __construct(String $name, int $price, int $stock)
    {
        $this->name = $name;
        $this->stock = $stock;
        $this->price = $price;
    }

    public function getId(): int|null
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    public function setPrice(int $price)
    {
        $this->price = $price;
    }
    public function getPrice(): int
    {
        return $this->price;
    }
    public function setStock(int $stock)
    {
        $this->stock = $stock;
    }
    public function getStock(): int
    {
        return $this->stock;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'stock' => $this->stock
        ];
    }
}
