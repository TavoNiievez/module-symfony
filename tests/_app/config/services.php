<?php

declare(strict_types=1);

namespace Tests\_app\config;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\DataCollector\HttpClientDataCollector;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Notifier\EventListener\NotificationLoggerListener;
use Symfony\Component\Security\Core\User\UserPasswordHasherInterface;
use Tests\_app\Command\DoctrineFixturesLoadCommand;
use Tests\_app\Command\ExampleCommand;
use Tests\_app\Command\HelloCommand;
use Tests\_app\Controller\AppController;
use Tests\_app\Doctrine\DoctrineSetup;
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

return function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure()->public();

    // Controllers
    $services->set(AppController::class);

    // Commands
    $services->set(HelloCommand::class)->tag('console.command', ['command' => 'app:hello']);
    $services->set(ExampleCommand::class)->tag('console.command', ['command' => 'app:example-command']);
    $services->set(DoctrineFixturesLoadCommand::class)->tag('console.command', ['command' => 'doctrine:fixtures:load']);

    // Doctrine
    $services->set('doctrine.orm.entity_manager', EntityManagerInterface::class)
        ->factory([DoctrineSetup::class, 'createEntityManager']);
    $services->alias('doctrine.orm.default_entity_manager', 'doctrine.orm.entity_manager')->public();

    $services->set('doctrine.dbal.default_connection', Connection::class)
        ->factory([DoctrineSetup::class, 'createConnection']);

    $services->set(UserRepository::class)->factory([DoctrineSetup::class, 'createUserRepository']);
    $services->alias(UserRepositoryInterface::class, UserRepository::class)->public();

    // Security
    $services->set('security.user.provider.test', TestUserProvider::class)
        ->arg('$repository', service(UserRepository::class))
        ->tag('security.user_provider');

    $services->alias('security.password_hasher', 'security.user_password_hasher')->public();
    $services->alias(UserPasswordHasherInterface::class, 'security.user_password_hasher')->public();

    if (class_exists(Security::class)) {
        $services->set(Security::class)->arg('$container', service('test.service_container'));
        $services->alias('security.helper', Security::class)->public();
    }

    // Mailer & Notifier
    $services->set('mailer.message_logger_listener', MessageLoggerListener::class)->tag('kernel.event_subscriber');
    $services->set('notifier.notification_logger_listener', NotificationLoggerListener::class)->tag('kernel.event_subscriber');
    $services->alias('notifier.logger_notification_listener', 'notifier.notification_logger_listener')->public();

    $services->set(RegistrationMailer::class)->arg('$mailer', service('mailer'));
    $services->set(NotifierFixture::class)->arg('$dispatcher', service('event_dispatcher'));

    // Events & Listeners
    $services->set(SampleEventListener::class)->tag('kernel.event_listener', ['event' => SampleEvent::class]);
    $services->set(NamedEventListener::class)->tag('kernel.event_listener', ['event' => 'named.event', 'method' => 'onNamedEvent']);

    // Logger
    $services->set('logger', ArrayLogger::class);
    $services->alias(LoggerInterface::class, 'logger')->public();

    // Twig Profiler
    $services->set(Profile::class);
    $services->set(ProfilerExtension::class)->arg('$profile', service(Profile::class))->tag('twig.extension');

    // HTTP Client
    $services->set(MockResponseFactory::class);

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
};
