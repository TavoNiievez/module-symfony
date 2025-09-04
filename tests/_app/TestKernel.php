<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;


class TestKernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'profiler' => ['enabled' => true, 'collect' => true, 'collect_serializer_data' => true],
            'property_info' => ['with_constructor_extractor' => false],
            'session' => [
                'handler_id' => null,
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
            'mailer' => ['dsn' => 'null://null'],
            'default_locale' => 'en',
            'translator' => [
                'default_path' => __DIR__ . '/translations',
                'fallbacks' => ['es'],
            ],
            'validation' => ['enabled' => true],
        ]);

        $container->extension('twig', [
            'default_path' => __DIR__ . '/templates',
        ]);

        $container->extension('security', [
            'password_hashers' => [
                PasswordAuthenticatedUserInterface::class => 'auto',
            ],
            'providers' => [
                'users_in_memory' => [
                    'memory' => [
                        'users' => [],
                    ],
                ],
            ],
            'firewalls' => [
                'main' => [
                    'lazy' => true,
                    'provider' => 'users_in_memory',
                    'remember_me' => ['secret' => 'test'],
                    'logout' => ['path' => 'logout'],
                ],
            ],
        ]);

        $container->parameters()->set('app.param', 'value');

        $services = $container->services();
        $services->set(HelloCommand::class, HelloCommand::class)
            ->tag('console.command', ['command' => 'app:hello'])
            ->public();
        $services->set(Security::class)
            ->public()
            ->arg('$container', service('test.service_container'));
        $services->alias('security.helper', Security::class)->public();
        $services->set('mailer.message_logger_listener', MessageLoggerListener::class)
            ->tag('kernel.event_subscriber')
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
        $routes->add('sample', '/sample')
            ->controller(self::class . '::sample');
        $routes->add('redirect', '/redirect')
            ->controller(self::class . '::redirect');
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
            ->controller(self::class . '::index');
    }

    public function index(): Response
    {
        return new Response('OK');
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

    public function redirect(): RedirectResponse
    {
        return new RedirectResponse('/sample');
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
       return new Response('Session');
    }

    public function deprecated(): Response
    {
        trigger_error('Deprecated endpoint', E_USER_DEPRECATED);
        return new Response('Deprecated');
    }

    public function sendEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('john_doe@example.com')
            ->to('jane_doe@example.com')
            ->subject('Test')
            ->text('Example text body')
            ->html('<p>HTML body</p>')
            ->attach('Attachment content', 'test.txt');

        $mailer->send($email);

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
}
