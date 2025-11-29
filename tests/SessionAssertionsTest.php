<?php

namespace Tests;

use Codeception\Module\Symfony\SessionAssertionsTrait;
use Codeception\Module\Symfony\SecurityAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Tests\_app\Entity\User;
use Tests\_app\Repository\UserRepository;

class SessionAssertionsTest extends KernelTestCase
{
    use SecurityAssertionsTrait;
    use SessionAssertionsTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->client = new KernelBrowser(self::$kernel);
    }

    protected static function getKernelClass(): string
    {
        return \TestKernel::class;
    }

    protected function getClient(): KernelBrowser
    {
        return $this->client;
    }

    protected function grabService(string $serviceId): object
    {
        return self::getContainer()->get($serviceId);
    }

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

    private function getTestUser(): User
    {
        /** @var UserRepository $repository */
        $repository = self::getContainer()->get(UserRepository::class);
        $user = $repository->getByEmail('john_doe@gmail.com');
        $this->assertNotNull($user);

        return $user;
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
