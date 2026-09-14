<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/doctrine.php';

use Doctrine\ORM\Tools\SchemaTool;

$em = getEntityManager();
$schemaTool = new SchemaTool($em);

// Ambil semua metadata entity di src/Entity
$metadata = $em->getMetadataFactory()->getAllMetadata();

if (empty($metadata)) {
    echo "Tidak ada entity ditemukan.\n";
    exit(1);
}

// Hapus lalu buat ulang (aman untuk contoh belajar / dev saja!)
$schemaTool->dropSchema($metadata);
$schemaTool->createSchema($metadata);

echo "Schema berhasil dibuat di var/database.sqlite\n";
