<?php

namespace Tests\_app;

require_once __DIR__ . '/Security/SecurityBundleSecurityAlias.php';

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\DataCollector\HttpClientDataCollector;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Notifier\EventListener\NotificationLoggerListener;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserPasswordHasherInterface;
use Tests\_app\Command\DoctrineFixturesLoadCommand;
use Tests\_app\Command\ExampleCommand;
use Tests\_app\Command\HelloCommand;
use Tests\_app\Controller\SecurityController;
use Tests\_app\Controller\TestController;
use Tests\_app\Entity\User;
use Tests\_app\Event\SampleEvent;
use Tests\_app\HttpClient\MockResponseFactory;
use Tests\_app\Listener\NamedEventListener;
use Tests\_app\Listener\SampleEventListener;
use Tests\_app\Logger\ArrayLogger;
use Tests\_app\Mailer\RegistrationMailer;
use Tests\_app\Notifier\NotifierFixture;
use Tests\_app\Repository\UserRepository;
use Tests\_app\Repository\UserRepositoryInterface;
use Tests\_app\Security\TestUserProvider;
use Twig\Profiler\Profile;
use Twig\Extension\ProfilerExtension;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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
        $this->configureServices($container);
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

    private function configureServices(ContainerConfigurator $container): void
    {
        $services = $container->services();
        $services->defaults()->autowire()->autoconfigure()->public();

        $services->set(TestController::class);
        $services->set(SecurityController::class);

        $services->set(HelloCommand::class)->tag('console.command', ['command' => 'app:hello']);
        $services->set(ExampleCommand::class)->tag('console.command', ['command' => 'app:example-command']);
        $services->set(DoctrineFixturesLoadCommand::class)->tag('console.command', ['command' => 'doctrine:fixtures:load']);

        $services->set('doctrine.orm.entity_manager', EntityManagerInterface::class)
            ->factory([self::class, 'createEntityManager']);
        $services->alias('doctrine.orm.default_entity_manager', 'doctrine.orm.entity_manager')->public();

        $services->set('doctrine.dbal.default_connection', Connection::class)
            ->factory([self::class, 'createConnection']);

        $services->set('security.user.provider.test', TestUserProvider::class)
            ->arg('$repository', service(UserRepository::class))
            ->tag('security.user_provider');

        $services->set(UserRepository::class)->factory([self::class, 'createUserRepository']);
        $services->alias(UserRepositoryInterface::class, UserRepository::class)->public();

        $services->alias('security.password_hasher', 'security.user_password_hasher')->public();
        $services->alias(UserPasswordHasherInterface::class, 'security.user_password_hasher')->public();

        $services->set(Security::class)->arg('$container', service('test.service_container'));
        $services->alias('security.helper', Security::class)->public();

        $services->set('mailer.message_logger_listener', MessageLoggerListener::class)->tag('kernel.event_subscriber');
        $services->set('notifier.notification_logger_listener', NotificationLoggerListener::class)->tag('kernel.event_subscriber');
        $services->alias('notifier.logger_notification_listener', 'notifier.notification_logger_listener')->public();

        $services->set(RegistrationMailer::class)->arg('$mailer', service('mailer'));
        $services->set(NotifierFixture::class)->arg('$dispatcher', service('event_dispatcher'));

        $services->set(SampleEventListener::class)->tag('kernel.event_listener', ['event' => SampleEvent::class]);
        $services->set(NamedEventListener::class)->tag('kernel.event_listener', ['event' => 'named.event', 'method' => 'onNamedEvent']);

        $services->set(MockResponseFactory::class);
        $services->set('logger', ArrayLogger::class);
        $services->alias(LoggerInterface::class, 'logger')->public();

        $services->set(Profile::class);
        $services->set(ProfilerExtension::class)->arg('$profile', service(Profile::class))->tag('twig.extension');

        $this->configureHttpClient($services);
    }

    private function configureHttpClient(\Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator $services): void
    {
        $services->set('app.http_client.inner', MockHttpClient::class)
            ->arg('$responseFactory', service(MockResponseFactory::class));

        $services->set('app.http_client', TraceableHttpClient::class)
            ->args([service('app.http_client.inner'), service('debug.stopwatch')->nullOnInvalid()]);

        $services->set('app.http_client.json_client.inner', MockHttpClient::class)
            ->args([service(MockResponseFactory::class), 'https://api.example.com/']);

        $services->set('app.http_client.json_client', TraceableHttpClient::class)
            ->args([service('app.http_client.json_client.inner'), service('debug.stopwatch')->nullOnInvalid()]);

        $services->set(HttpClientDataCollector::class)
            ->call('registerClient', ['app.http_client', service('app.http_client')])
            ->call('registerClient', ['app.http_client.json_client', service('app.http_client.json_client')])
            ->tag('data_collector', ['id' => 'http_client', 'template' => '@WebProfiler/Collector/http_client.html.twig', 'priority' => 100]);
        $services->alias('data_collector.http_client', HttpClientDataCollector::class)->public();

        $services->set(LoggerDataCollector::class)
            ->arg('$logger', service('logger'))
            ->tag('data_collector', ['id' => 'logger', 'template' => '@WebProfiler/Collector/logger.html.twig', 'priority' => 300]);
        $services->alias('data_collector.logger', LoggerDataCollector::class)->public();

        if (BaseKernel::VERSION_ID < 60100) {
            $services->defaults()
                ->bind(\Symfony\Contracts\HttpClient\HttpClientInterface::class . ' $httpClient', service('app.http_client'))
                ->bind(\Symfony\Contracts\HttpClient\HttpClientInterface::class . ' $jsonClient', service('app.http_client.json_client'));
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('index', '/')->controller(TestController::class . '::index');
        $routes->add('app_login', '/login')->controller(SecurityController::class . '::login');
        $routes->add('app_register', '/register')->controller(SecurityController::class . '::register');
        $routes->add('dashboard', '/dashboard')->controller(SecurityController::class . '::dashboard');
        $routes->add('logout', '/logout')->controller(SecurityController::class . '::logout');

        $routes->add('sample', '/sample')->controller(TestController::class . '::sample');
        $routes->add('request_attr', '/request_attr')->controller(TestController::class . '::requestWithAttribute');
        $routes->add('response_cookie', '/response_cookie')->controller(TestController::class . '::responseWithCookie');
        $routes->add('response_json', '/response_json')->controller(TestController::class . '::responseJsonFormat');
        $routes->add('test_page', '/test_page')->controller(TestController::class . '::testPage');
        $routes->add('unprocessable_entity', '/unprocessable_entity')->controller(TestController::class . '::unprocessableEntity');
        $routes->add('redirect', '/redirect')->controller(TestController::class . '::redirectToSample');
        $routes->add('redirect_home', '/redirect_home')->controller(TestController::class . '::redirectToHome');
        $routes->add('unprocessable', '/unprocessable')->controller(TestController::class . '::unprocessable');
        $routes->add('session', '/session')->controller(TestController::class . '::session');
        $routes->add('deprecated', '/deprecated')->controller(TestController::class . '::deprecated');
        $routes->add('send_email', '/send-email')->controller(TestController::class . '::sendEmail');
        $routes->add('translation', '/translation')->controller(TestController::class . '::translation');
        $routes->add('twig', '/twig')->controller(TestController::class . '::twig');
        $routes->add('dispatch_event', '/dispatch-event')->controller(TestController::class . '::dispatchEvent');
        $routes->add('dispatch_named_event', '/dispatch-named-event')->controller(TestController::class . '::dispatchNamedEvent');
        $routes->add('dispatch_orphan_event', '/dispatch-orphan-event')->controller(TestController::class . '::dispatchOrphanEvent');
        $routes->add('form_handler', '/form')->controller(TestController::class . '::form');
        $routes->add('http_client', '/http-client')->controller(TestController::class . '::httpClientRequests');
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
