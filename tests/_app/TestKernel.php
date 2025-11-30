<?php

declare(strict_types=1);

namespace Tests\_app;

require_once __DIR__ . '/Security/SecurityBundleSecurityAlias.php';
require_once __DIR__ . '/Command/TestCommands.php';
require_once __DIR__ . '/Event/TestEvents.php';
require_once __DIR__ . '/Listener/TestListeners.php';

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Tests\_app\Entity\User;
use Tests\_app\Repository\UserRepository;

class TestKernel extends BaseKernel
{
    use MicroKernelTrait;

    private static ?EntityManagerInterface $entityManager = null;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $this->configureExtensions($container);

        // Load services from config file
        $container->import(__DIR__ . '/config/services.php');
    }

    private function configureExtensions(ContainerConfigurator $container): void
    {
        $profilerConfig = ['enabled' => true, 'collect' => true];
        if (BaseKernel::VERSION_ID >= 60200 && class_exists(\Symfony\Component\Serializer\DataCollector\SerializerDataCollector::class)) {
            $profilerConfig['collect_serializer_data'] = true;
        }

        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'profiler' => $profilerConfig,
            'property_info' => ['enabled' => true],
            'session' => ['handler_id' => null, 'storage_factory_id' => 'session.storage.factory.mock_file'],
            'mailer' => ['dsn' => 'null://null'],
            'default_locale' => 'en',
            'translator' => ['default_path' => __DIR__ . '/translations', 'fallbacks' => ['es'], 'logging' => true],
            'validation' => ['enabled' => true],
            'form' => ['enabled' => true],
            'notifier' => ['chatter_transports' => ['async' => 'null://null'], 'texter_transports' => ['sms' => 'null://null']],
        ]);

        $container->extension('twig', ['default_path' => __DIR__ . '/templates', 'debug' => true]);

        $this->configureSecurity($container);
    }

    private function configureSecurity(ContainerConfigurator $container): void
    {
        $mainFirewall = [
            'lazy' => BaseKernel::VERSION_ID >= 60000,
            'pattern' => '^/',
            'provider' => 'doctrine_users',
            'logout' => ['path' => 'logout'],
            'form_login' => ['login_path' => 'app_login', 'check_path' => 'app_login'],
            'remember_me' => ['secret' => 'test', 'remember_me_parameter' => '_remember_me'],
        ];

        if (BaseKernel::VERSION_ID < 60000) {
            $mainFirewall['anonymous'] = true;
        }

        $container->extension('security', [
            'password_hashers' => [PasswordAuthenticatedUserInterface::class => 'auto'],
            'providers' => ['doctrine_users' => ['id' => 'security.user.provider.test']],
            'firewalls' => ['main' => $mainFirewall],
        ]);

        $container->parameters()->set('app.param', 'value');
        $container->parameters()->set('app.business_name', 'Codeception');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // Load routes from config file
        $routes->import(__DIR__ . '/config/routes.php');
    }

    public static function createEntityManager(): EntityManagerInterface
    {
        if (self::$entityManager !== null && self::$entityManager->isOpen()) {
            return self::$entityManager;
        }

        if (method_exists(ORMSetup::class, 'createAttributeMetadataConfig')) {
            $config = ORMSetup::createAttributeMetadataConfig([__DIR__ . '/Entity'], true);
        } else {
            $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity'], true);
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
