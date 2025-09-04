<?php

namespace Tests;

use Codeception\Module\Symfony\SessionAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

class SessionAssertionsTest extends KernelTestCase
{
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

    public function testSessionAssertions(): void
    {
        $container = self::getContainer();
        $factory = $container->get('session.factory');
        $session = $factory->createSession();
        $container->set('session', $session);
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

    public function testLoginAndLogoutAssertions(): void
    {
        $user = new InMemoryUser('john@example.com', null, ['ROLE_USER']);

        $this->amLoggedInAs($user);
        $this->seeInSession('_security_main');
        $this->logout();
        $this->dontSeeInSession('_security_main');

        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);
        $this->amLoggedInWithToken($token);
        $this->seeInSession('_security_main');
        $this->goToLogoutPath();

        $this->amLoggedInAs($user);
        $this->seeInSession('_security_main');
        $this->logoutProgrammatically();
        $this->dontSeeInSession('_security_main');
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
