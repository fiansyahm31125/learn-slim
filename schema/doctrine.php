<?php
// bin/doctrine
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
// Adjust this path to your actual bootstrap.php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';
ConsoleRunner::run(
    new SingleManagerProvider(getEntityManager())
);

// run  php schema/doctrine.php orm:schema-tool:create