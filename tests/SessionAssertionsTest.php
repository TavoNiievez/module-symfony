<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\SessionAssertionsTrait;
use Codeception\Module\Symfony\SecurityAssertionsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Tests\_app\Entity\User;
use Tests\_app\Repository\UserRepository;
use Tests\Support\KernelTestCase;

class SessionAssertionsTest extends KernelTestCase
{
    use SecurityAssertionsTrait;
    use SessionAssertionsTrait;

    public function testAmLoggedInAs(): void
    {
        $user = $this->getTestUser();

        $this->amLoggedInAs($user);
        $this->client->request('GET', '/dashboard');

        $this->seeAuthentication();
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('You are in the Dashboard!', $this->client->getResponse()->getContent());
    }

    public function testAmLoggedInWithToken(): void
    {
        $user = $this->getTestUser();
        $token = new PostAuthenticationToken($user, 'main', $user->getRoles());

        $this->amLoggedInWithToken($token);
        $this->client->request('GET', '/dashboard');

        $this->seeAuthentication();
        $this->assertStringContainsString('You are in the Dashboard!', $this->client->getResponse()->getContent());
    }

    public function testDontSeeInSession(): void
    {
        $this->client->request('GET', '/');
        $this->dontSeeInSession('_security_main');

        $this->initSession(['key1' => 'value1']);
        $this->dontSeeInSession('missing');
        $this->dontSeeInSession('key1', 'other');
    }

    public function testGoToLogoutPath(): void
    {
        $user = $this->getTestUser();
        $this->amLoggedInAs($user);
        $this->client->request('GET', '/dashboard');
        $this->assertStringContainsString('You are in the Dashboard!', $this->client->getResponse()->getContent());

        $this->goToLogoutPath();
        $this->assertSame('/logout', $this->client->getRequest()->getPathInfo());
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->client->followRedirect();

        $this->dontSeeAuthentication();
        $this->assertSame('/', $this->client->getRequest()->getPathInfo());
    }

    public function testLogout(): void
    {
        $user = $this->getTestUser();
        $this->amLoggedInAs($user);

        $this->logout();
        $this->client->request('GET', '/dashboard');

        $this->dontSeeAuthentication();
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testLogoutProgrammatically(): void
    {
        $user = $this->getTestUser();
        $this->amLoggedInAs($user);

        $this->logoutProgrammatically();
        $this->client->request('GET', '/dashboard');

        $this->dontSeeAuthentication();
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testSeeInSession(): void
    {
        $this->initSession(['key1' => 'value1']);

        $this->seeInSession('key1');
        $this->seeInSession('key1', 'value1');
    }

    public function testSeeSessionHasValues(): void
    {
        $this->initSession(['key1' => 'value1', 'key2' => 'value2']);

        $this->seeSessionHasValues(['key1', 'key2']);
        $this->seeSessionHasValues(['key1' => 'value1', 'key2' => 'value2']);
    }

    private function getTestUser(): User
    {
        /** @var UserRepository $repository */
        $repository = $this->_getContainer()->get(UserRepository::class);
        $user = $repository->getByEmail('john_doe@gmail.com');
        $this->assertNotNull($user);

        return $user;
    }

    private function initSession(array $data): void
    {
        if ($this->_getContainer()->has('session')) {
            $session = $this->_getContainer()->get('session');
        } else {
            $factory = $this->_getContainer()->get('session.factory');
            $session = $factory->createSession();
            $this->_getContainer()->set('session', $session);
        }

        foreach ($data as $key => $value) {
            $session->set($key, $value);
        }
        $session->save();
    }
}
