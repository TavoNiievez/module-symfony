<?php

declare(strict_types=1);

namespace Tests\_app\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\_app\Event\NamedEvent;
use Tests\_app\Event\OrphanEvent;
use Tests\_app\Event\SampleEvent;
use Tests\_app\Mailer\RegistrationMailer;
use Twig\Environment;

class AppController extends AbstractController
{
    // --- Test Actions ---

    public function deprecated(LoggerInterface $logger): Response
    {
        trigger_error('Deprecated endpoint', E_USER_DEPRECATED);
        $logger->info('Deprecated endpoint', ['scream' => false]);

        return new Response('Deprecated');
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

    public function httpClientRequests(
        #[Autowire(service: 'app.http_client')]
        HttpClientInterface $httpClient,
        #[Autowire(service: 'app.http_client.json_client')]
        HttpClientInterface $jsonClient,
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

    public function index(): Response
    {
        return new Response('Hello World!');
    }

    public function redirectToHome(): RedirectResponse
    {
        return new RedirectResponse('/');
    }

    public function redirectToSample(): RedirectResponse
    {
        return new RedirectResponse('/sample');
    }

    public function requestWithAttribute(Request $request): Response
    {
        $request->attributes->set('page', 'register');

        return new Response('Request attribute set');
    }

    public function responseJsonFormat(Request $request): JsonResponse
    {
        $request->setRequestFormat('json');

        return new JsonResponse([
            'status' => 'success',
            'message' => "Expected format: 'json'.",
        ]);
    }

    public function responseWithCookie(): Response
    {
        $response = new Response('TESTCOOKIE has been set.');
        $response->headers->setCookie(new Cookie('TESTCOOKIE', 'codecept'));

        return $response;
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

    public function sendEmail(RegistrationMailer $mailer): Response
    {
        $mailer->sendConfirmationEmail('jane_doe@example.com');

        return new Response('Email sent');
    }

    public function session(Request $request): Response
    {
        $session = $request->getSession();
        $session->set('key1', 'value1');
        $session->set('key2', 'value2');
        $session->save();

        return new Response('Session');
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

    public function translation(TranslatorInterface $translator): Response
    {
        $translator->trans('defined_message');
        return new Response('Translation');
    }

    public function twig(Environment $twig): Response
    {
        return new Response($twig->render('home.html.twig'));
    }

    public function unprocessable(): Response
    {
        return new Response('Unprocessable', 422);
    }

    public function unprocessableEntity(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'The request was well-formed but could not be processed.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // --- Security Actions ---

    public function dashboard(TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();
        if ($token === null || !is_object($token->getUser())) {
            return new RedirectResponse('/login');
        }

        return new Response('You are in the Dashboard!');
    }

    public function login(Environment $twig): Response
    {
        return new Response($twig->render('security/login.html.twig'));
    }

    public function logout(Request $request, TokenStorageInterface $tokenStorage): RedirectResponse
    {
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

    public function register(Request $request, Environment $twig): Response
    {
        if ($request->isMethod('POST')) {
            return new RedirectResponse('/dashboard');
        }

        return new Response($twig->render('security/register.html.twig'));
    }
}
