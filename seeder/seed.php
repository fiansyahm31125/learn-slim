<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';

use App\Entity\Product;

$em = getEntityManager();

// Data dummy sederhana — 5 produk
$dummies = [
    ['Kopi Arabika 200g', 85000, 20],
    ['Teh Hijau 100g', 35000, 50],
    ['Gula Aren 500g', 28000, 100],
    ['Mie Instan Goreng (dus)', 120000, 15],
    ['Minyak Goreng 2L', 48000, 30],
];

foreach ($dummies as [$name, $price, $stock]) {
    $em->persist(new Product($name, $price, $stock));
}
$em->flush();

$total = count($em->getRepository(Product::class)->findAll());
echo "Seed selesai. Total produk di DB: {$total}\n";

// php seeder/seed.php