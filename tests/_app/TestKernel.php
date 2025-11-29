<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpClient\DataCollector\HttpClientDataCollector;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Notifier\EventListener\NotificationLoggerListener;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Tests\_app\Security\TestUserProvider;
use Twig\Environment;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Tests\_app\DoctrineFixturesLoadCommand;
use Tests\_app\Entity\User;
use Tests\_app\Event\NamedEvent;
use Tests\_app\Event\OrphanEvent;
use Tests\_app\Event\SampleEvent;
use Tests\_app\ExampleCommand;
use Tests\_app\HelloCommand;
use Tests\_app\HttpClient\MockResponseFactory;
use Tests\_app\Logger\ArrayLogger;
use Tests\_app\Listener\NamedEventListener;
use Tests\_app\Listener\SampleEventListener;
use Tests\_app\Mailer\RegistrationMailer;
use Tests\_app\Notifier\NotifierFixture;
use Tests\_app\Repository\Model\UserRepositoryInterface;
use Tests\_app\Repository\UserRepository;
use Doctrine\DBAL\DriverManager;


class TestKernel extends BaseKernel
{
    use MicroKernelTrait;

    private static ?EntityManagerInterface $entityManager = null;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'profiler' => ['enabled' => true, 'collect' => true, 'collect_serializer_data' => true],
            'property_info' => ['enabled' => true],
            'session' => [
                'handler_id' => null,
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
            'mailer' => ['dsn' => 'null://null'],
            'default_locale' => 'en',
            'translator' => [
                'default_path' => __DIR__ . '/translations',
                'fallbacks' => ['es'],
                'logging' => true,
            ],
            'validation' => ['enabled' => true],
            'form' => ['enabled' => true],
            'notifier' => [
                'chatter_transports' => ['async' => 'null://null'],
                'texter_transports' => ['sms' => 'null://null'],
            ],
        ]);

        $container->extension('twig', [
            'default_path' => __DIR__ . '/templates',
            'debug' => true,
        ]);

        $container->extension('security', [
            'password_hashers' => [
                PasswordAuthenticatedUserInterface::class => 'auto',
            ],
            'providers' => [
                'doctrine_users' => [
                    'id' => 'security.user.provider.test',
                ],
            ],
            'firewalls' => [
                'main' => [
                    'lazy' => true,
                    'provider' => 'doctrine_users',
                    'remember_me' => ['secret' => 'test'],
                    'logout' => ['path' => 'logout'],
                ],
            ],
        ]);

        $container->parameters()->set('app.param', 'value');
        $container->parameters()->set('app.business_name', 'Codeception');

        $services = $container->services();
        $services->set(HelloCommand::class, HelloCommand::class)
            ->tag('console.command', ['command' => 'app:hello'])
            ->public();
        $services->set(ExampleCommand::class, ExampleCommand::class)
            ->tag('console.command', ['command' => 'app:example-command'])
            ->public();
        $services->set(DoctrineFixturesLoadCommand::class, DoctrineFixturesLoadCommand::class)
            ->tag('console.command', ['command' => 'doctrine:fixtures:load'])
            ->public();
        $services->set('doctrine.orm.entity_manager', EntityManagerInterface::class)
            ->factory([self::class, 'createEntityManager'])
            ->public()
            ->share(true);
        $services->alias('doctrine.orm.default_entity_manager', 'doctrine.orm.entity_manager')->public();
        $services->set('doctrine.dbal.default_connection', Connection::class)
            ->factory([self::class, 'createConnection'])
            ->public()
            ->share(true);
        $services->set('security.user.provider.test', TestUserProvider::class)
            ->arg('$repository', service(UserRepository::class))
            ->tag('security.user_provider')
            ->public();
        $services->set(UserRepository::class)
            ->factory([self::class, 'createUserRepository'])
            ->public();
        $services->alias(UserRepositoryInterface::class, UserRepository::class)->public();
        $services->set(Security::class)
            ->public()
            ->arg('$container', service('test.service_container'));
        $services->alias('security.helper', Security::class)->public();
        $services->set('mailer.message_logger_listener', MessageLoggerListener::class)
            ->tag('kernel.event_subscriber')
            ->public();
        $services->set('notifier.notification_logger_listener', NotificationLoggerListener::class)
            ->tag('kernel.event_subscriber')
            ->public();
        $services->alias('notifier.logger_notification_listener', 'notifier.notification_logger_listener')->public();
        $services->set(RegistrationMailer::class)
            ->arg('$mailer', service('mailer'))
            ->public();
        $services->set(NotifierFixture::class)
            ->arg('$dispatcher', service('event_dispatcher'))
            ->public();
        $services->set(SampleEventListener::class)
            ->tag('kernel.event_listener', ['event' => SampleEvent::class])
            ->public();
        $services->set(NamedEventListener::class)
            ->tag('kernel.event_listener', ['event' => 'named.event', 'method' => 'onNamedEvent'])
            ->public();
        $services->set(MockResponseFactory::class)
            ->public();
        $services->set('logger', ArrayLogger::class)
            ->public();
        $services->alias(LoggerInterface::class, 'logger')->public();
        $services->set('app.http_client.inner', MockHttpClient::class)
            ->arg('$responseFactory', service(MockResponseFactory::class))
            ->public();
        $services->set('app.http_client', TraceableHttpClient::class)
            ->args([service('app.http_client.inner'), service('debug.stopwatch')->nullOnInvalid()])
            ->public();
        $services->set('app.http_client.json_client.inner', MockHttpClient::class)
            ->args([service(MockResponseFactory::class), 'https://api.example.com/'])
            ->public();
        $services->set('app.http_client.json_client', TraceableHttpClient::class)
            ->args([service('app.http_client.json_client.inner'), service('debug.stopwatch')->nullOnInvalid()])
            ->public();
        $services->set(HttpClientDataCollector::class)
            ->public()
            ->call('registerClient', ['app.http_client', service('app.http_client')])
            ->call('registerClient', ['app.http_client.json_client', service('app.http_client.json_client')])
            ->tag('data_collector', [
                'id' => 'http_client',
                'template' => '@WebProfiler/Collector/http_client.html.twig',
                'priority' => 100,
            ]);
        $services->alias('data_collector.http_client', HttpClientDataCollector::class)->public();
        $services->set(LoggerDataCollector::class)
            ->public()
            ->arg('$logger', service('logger'))
            ->tag('data_collector', [
                'id' => 'logger',
                'template' => '@WebProfiler/Collector/logger.html.twig',
                'priority' => 300,
            ]);
        $services->alias('data_collector.logger', LoggerDataCollector::class)->public();
        $services->set(Profile::class)
            ->public();
        $services->set(ProfilerExtension::class)
            ->arg('$profile', service(Profile::class))
            ->tag('twig.extension')
            ->public();
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
        ];
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('index', '/')
            ->controller(self::class . '::index');
        $routes->add('app_login', '/login')
            ->controller(self::class . '::login');
        $routes->add('app_register', '/register')
            ->controller(self::class . '::register');
        $routes->add('dashboard', '/dashboard')
            ->controller(self::class . '::dashboard');
        $routes->add('sample', '/sample')
            ->controller(self::class . '::sample');
        $routes->add('request_attr', '/request_attr')
            ->controller(self::class . '::requestWithAttribute');
        $routes->add('response_cookie', '/response_cookie')
            ->controller(self::class . '::responseWithCookie');
        $routes->add('response_json', '/response_json')
            ->controller(self::class . '::responseJsonFormat');
        $routes->add('test_page', '/test_page')
            ->controller(self::class . '::testPage');
        $routes->add('unprocessable_entity', '/unprocessable_entity')
            ->controller(self::class . '::unprocessableEntity');
        $routes->add('redirect', '/redirect')
            ->controller(self::class . '::redirect');
        $routes->add('redirect_home', '/redirect_home')
            ->controller(self::class . '::redirectToHome');
        $routes->add('unprocessable', '/unprocessable')
            ->controller(self::class . '::unprocessable');
        $routes->add('session', '/session')
            ->controller(self::class . '::session');
        $routes->add('deprecated', '/deprecated')
            ->controller(self::class . '::deprecated');
        $routes->add('send_email', '/send-email')
            ->controller(self::class . '::sendEmail');
        $routes->add('translation', '/translation')
            ->controller(self::class . '::translation');
        $routes->add('twig', '/twig')
            ->controller(self::class . '::twig');
        $routes->add('logout', '/logout')
            ->controller(self::class . '::logout');
        $routes->add('dispatch_event', '/dispatch-event')
            ->controller(self::class . '::dispatchEvent');
        $routes->add('dispatch_named_event', '/dispatch-named-event')
            ->controller(self::class . '::dispatchNamedEvent');
        $routes->add('dispatch_orphan_event', '/dispatch-orphan-event')
            ->controller(self::class . '::dispatchOrphanEvent');
        $routes->add('form_handler', '/form')
            ->controller(self::class . '::form');
        $routes->add('http_client', '/http-client')
            ->controller(self::class . '::httpClientRequests');
    }

    public function index(): Response
    {
        return new Response('Hello World!');
    }

    public function login(Environment $twig): Response
    {
        return new Response($twig->render('security/login.html.twig'));
    }

    public function register(Request $request, Environment $twig): Response
    {
        if ($request->isMethod('POST')) {
            return new RedirectResponse('/dashboard');
        }

        return new Response($twig->render('security/register.html.twig'));
    }

    public function logout(Request $request): RedirectResponse
    {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->getContainer()->get('test.service_container')->get('security.token_storage');
        $tokenStorage->setToken(null);

        $sessionName = null;
        if ($request->hasSession()) {
            $session = $request->getSession();
            $sessionName = $session->getName();
            $session->invalidate();
        }

        $response = new RedirectResponse('/');
        if ($sessionName !== null) {
            $response->headers->clearCookie($sessionName);
        }
        $response->headers->clearCookie('MOCKSESSID');
        $response->headers->clearCookie('REMEMBERME');

        return $response;
    }

    public function dashboard(): Response
    {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->getContainer()->get('test.service_container')->get('security.token_storage');
        $token = $tokenStorage->getToken();
        if ($token === null || !is_object($token->getUser())) {
            return new RedirectResponse('/login');
        }

        return new Response('You are in the Dashboard!');
    }

    public function sample(Request $request): Response
    {
        $request->attributes->set('foo', 'bar');
        $html = <<<HTML
<html>
  <head><title>Test Page</title></head>
  <body>
    <input type="checkbox" name="agree" checked="checked">
    <input type="checkbox" name="subscribe">
    <input type="text" name="username" value="john">
    <input type="text" name="empty">
    <form id="testForm" name="testForm" method="post">
      <input type="text" name="field1" value="value1">
    </form>
    <div id="greeting">Hello World</div>
  </body>
</html>
HTML;
        $response = new Response($html, 200, ['X-Test' => '1']);
        $response->headers->setCookie(new Cookie('response_cookie', 'yum'));
        return $response;
    }

    public function testPage(): Response
    {
        $html = <<<HTML
<html>
  <head><title>Test Page</title></head>
  <body>
    <h1>Test Page</h1>
    <input type="checkbox" name="exampleCheckbox" checked="checked" />
    <input type="text" name="exampleInput" value="Expected Value" />
  </body>
</html>
HTML;

        return new Response($html);
    }

    public function redirect(): RedirectResponse
    {
        return new RedirectResponse('/sample');
    }

    public function requestWithAttribute(Request $request): Response
    {
        $request->attributes->set('page', 'register');

        return new Response('Request attribute set');
    }

    public function responseWithCookie(): Response
    {
        $response = new Response('TESTCOOKIE has been set.');
        $response->headers->setCookie(new Cookie('TESTCOOKIE', 'codecept'));

        return $response;
    }

    public function responseJsonFormat(Request $request): JsonResponse
    {
        $request->setRequestFormat('json');

        return new JsonResponse([
            'status' => 'success',
            'message' => "Expected format: 'json'.",
        ]);
    }

    public function unprocessableEntity(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'The request was well-formed but could not be processed.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function redirectToHome(): RedirectResponse
    {
        return new RedirectResponse('/');
    }

    public function unprocessable(): Response
    {
        return new Response('Unprocessable', 422);
    }

    public function session(Request $request): Response
    {
        $session = $request->getSession();
        $session->set('key1', 'value1');
        $session->set('key2', 'value2');
        $session->save();

        $this->getContainer()->set('session', $session);

        return new Response('Session');
    }

    public function deprecated(LoggerInterface $logger): Response
    {
        trigger_error('Deprecated endpoint', E_USER_DEPRECATED);
        $logger->info('Deprecated endpoint', ['scream' => false]);

        return new Response('Deprecated');
    }

    public function sendEmail(RegistrationMailer $mailer): Response
    {
        $mailer->sendConfirmationEmail('jane_doe@example.com');

        return new Response('Email sent');
    }

    public function translation(TranslatorInterface $translator): Response
    {
        $translator->trans('defined_message');
        return new Response('Translation');
    }

    public function twig(Environment $twig): Response
    {
        return new Response($twig->render('home.html.twig'));
    }

    public function dispatchEvent(EventDispatcherInterface $dispatcher): Response
    {
        $dispatcher->dispatch(new SampleEvent());

        return new Response('Event dispatched');
    }

    public function dispatchNamedEvent(EventDispatcherInterface $dispatcher): Response
    {
        $dispatcher->dispatch(new NamedEvent(), 'named.event');

        return new Response('Named event dispatched');
    }

    public function dispatchOrphanEvent(EventDispatcherInterface $dispatcher): Response
    {
        $dispatcher->dispatch(new OrphanEvent());

        return new Response('Orphan event dispatched');
    }

    public function httpClientRequests(
        #[Autowire(service: 'app.http_client')] HttpClientInterface $httpClient,
        #[Autowire(service: 'app.http_client.json_client')] HttpClientInterface $jsonClient,
    ): Response {
        $httpClient->request('GET', 'https://example.com/default', [
            'headers' => ['X-Test' => 'yes'],
        ]);
        $httpClient->request('POST', 'https://example.com/body', [
            'json' => ['example' => 'payload'],
        ]);
        $jsonClient->request('GET', 'https://api.example.com/resource', [
            'headers' => ['Accept' => 'application/json'],
        ]);

        return new Response('HTTP client calls executed');
    }

    public function form(Request $request, FormFactoryInterface $formFactory): Response
    {
        $builder = $formFactory->createNamedBuilder('registration_form', options: ['csrf_protection' => false]);
        $builder->add('email', EmailType::class, [
            'constraints' => [new NotBlank(), new EmailConstraint()],
        ]);
        $builder->add('password', PasswordType::class, [
            'constraints' => [new NotBlank()],
        ]);
        $form = $builder->getForm();

        $form->handleRequest($request);

        $content = <<<HTML
<html>
  <body>
    <form name="{$form->getName()}" method="post">
      <input type="email" name="registration_form[email]" />
      <input type="password" name="registration_form[password]" />
      <button type="submit">Submit</button>
    </form>
  </body>
</html>
HTML;

        $status = $form->isSubmitted() && !$form->isValid() ? 422 : 200;

        return new Response($content, $status);
    }

    public static function createEntityManager(): EntityManagerInterface
    {
        if (self::$entityManager !== null && self::$entityManager->isOpen()) {
            return self::$entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfig([__DIR__ . '/Entity'], true);
        $proxyDir = sys_get_temp_dir() . '/doctrine-proxies';
        if (!is_dir($proxyDir)) {
            mkdir($proxyDir, 0777, true);
        }
        $config->setProxyDir($proxyDir);
        $config->setProxyNamespace('TestsProxies');
        $config->setAutoGenerateProxyClasses(true);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $entityManager = new EntityManager($connection, $config);

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
