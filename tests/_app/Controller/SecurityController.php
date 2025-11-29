<?php

namespace Tests\_app\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class SecurityController extends AbstractController
{
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

    public function dashboard(TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();
        if ($token === null || !is_object($token->getUser())) {
            return new RedirectResponse('/login');
        }

        return new Response('You are in the Dashboard!');
    }
}
