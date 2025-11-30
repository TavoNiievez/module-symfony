<?php

declare(strict_types=1);

namespace Tests\_app\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Tests\_app\Entity\User;
use Tests\_app\Repository\UserRepository;

class DoctrineSetup
{
    private static ?EntityManagerInterface $entityManager = null;

    public static function createEntityManager(): EntityManagerInterface
    {
        if (self::$entityManager !== null && self::$entityManager->isOpen()) {
            return self::$entityManager;
        }

        $entityDir = dirname(__DIR__) . '/Entity';

        if (method_exists(ORMSetup::class, 'createAttributeMetadataConfig')) {
            $config = ORMSetup::createAttributeMetadataConfig([$entityDir], true);
        } else {
            $config = ORMSetup::createAttributeMetadataConfiguration([$entityDir], true);
        }

        $proxyDir = sys_get_temp_dir() . '/doctrine-proxies';
        if (!is_dir($proxyDir)) {
            mkdir($proxyDir, 0777, true);
        }
        $config->setProxyDir($proxyDir);
        $config->setProxyNamespace('TestsProxies');
        $config->setAutoGenerateProxyClasses(true);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        if (method_exists(EntityManager::class, 'create')) {
            $entityManager = EntityManager::create($connection, $config);
        } else {
            $entityManager = new EntityManager($connection, $config);
        }

        $schemaTool = new SchemaTool($entityManager);
        $metadata = [$entityManager->getClassMetadata(User::class)];
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $user = User::create('john_doe@gmail.com', 'secret', ['ROLE_TEST']);
        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        self::$entityManager = $entityManager;

        return $entityManager;
    }

    public static function createConnection(): Connection
    {
        return self::createEntityManager()->getConnection();
    }

    public static function createUserRepository(): UserRepository
    {
        /** @var UserRepository $repository */
        $repository = self::createEntityManager()->getRepository(User::class);
        return $repository;
    }
}
