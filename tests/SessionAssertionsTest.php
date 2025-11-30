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

    protected function _getContainer(): ContainerInterface
    {
        return self::getContainer();
    }

    public function testAmLoggedInAsShowsDashboard(): void
    {
        $user = $this->getTestUser();

        $this->amLoggedInAs($user);
        $this->client->request('GET', '/dashboard');

        $this->seeAuthentication();
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('You are in the Dashboard!', $this->client->getResponse()->getContent());
    }

    public function testAmLoggedInWithTokenShowsDashboard(): void
    {
        $user = $this->getTestUser();
        $token = new PostAuthenticationToken($user, 'main', $user->getRoles());

        $this->amLoggedInWithToken($token);
        $this->client->request('GET', '/dashboard');

        $this->seeAuthentication();
        $this->assertStringContainsString('You are in the Dashboard!', $this->client->getResponse()->getContent());
    }

    public function testDontSeeInSessionWhenAnonymous(): void
    {
        $this->client->request('GET', '/');

        $this->dontSeeInSession('_security_main');
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

    public function testLogoutProgrammatically(): void
    {
        $user = $this->getTestUser();
        $this->amLoggedInAs($user);

        $this->logoutProgrammatically();
        $this->client->request('GET', '/dashboard');

        $this->dontSeeAuthentication();
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertSame('/login', $this->client->getResponse()->headers->get('Location'));
    }

    public function testSessionAssertions(): void
    {
        if (self::getContainer()->has('session')) {
            $session = self::getContainer()->get('session');
        } else {
            $factory = self::getContainer()->get('session.factory');
            $session = $factory->createSession();
            self::getContainer()->set('session', $session);
        }

        $session->set('key1', 'value1');
        $session->set('key2', 'value2');
        $session->save();

        $this->seeInSession('key1');
        $this->seeInSession('key1', 'value1');
        $this->dontSeeInSession('missing');
        $this->dontSeeInSession('key1', 'other');
        $this->seeSessionHasValues(['key1', 'key2']);
        $this->seeSessionHasValues(['key1' => 'value1', 'key2' => 'value2']);
    }

    private function getTestUser(): User
    {
        /** @var UserRepository $repository */
        $repository = self::getContainer()->get(UserRepository::class);
        $user = $repository->getByEmail('john_doe@gmail.com');
        $this->assertNotNull($user);

        return $user;
    }
}
