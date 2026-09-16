<?php
// bootstrap.php
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;


function getEntityManager(): EntityManager
{
    // Create a simple "default" Doctrine ORM configuration for Attributes
    $config = ORMSetup::createAttributeMetadataConfiguration( // on PHP < 8.4, use ORMSetup::createAttributeMetadataConfiguration()
        paths: [__DIR__ . '/../src/Entity'],
        isDevMode: true,
    );
    // configuring the database connection
    $connection = DriverManager::getConnection([
        'driver'   => 'pdo_mysql',
        'host'     => '127.0.0.1',
        'port'     => 3308,
        'dbname'   => 'learn_slim',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4'
    ], $config);
    // obtaining the entity manager
    $entityManager = new EntityManager($connection, $config);
    return $entityManager;
}
