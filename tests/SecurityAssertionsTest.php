<?php

namespace Tests;

use Codeception\Module\Symfony\SecurityAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\SecurityBundle\Security;

class SecurityAssertionsTest extends KernelTestCase
{
    use SecurityAssertionsTrait;

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

    protected function getService(string $serviceId): object
    {
        return self::getContainer()->get($serviceId);
    }

    protected function grabSecurityService()
    {
        return new Security(self::getContainer());
    }

    public function testDontSeeAuthentication(): void
    {
        $this->client->request('GET', '/dashboard');

        $this->dontSeeAuthentication();
    }

    public function testDontSeeRememberedAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_USER']);
        $this->client->loginUser($user);

        $this->dontSeeRememberedAuthentication();
    }

    public function testSeeAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_USER']);
        $this->client->loginUser($user);

        $this->seeAuthentication();
    }

    public function testSeeRememberedAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_USER']);
        $this->client->loginUser($user);
        $this->client->getCookieJar()->set(new Cookie('REMEMBERME', 'test-remember')); 

        $this->seeRememberedAuthentication();
    }

    public function testSeeUserHasRole(): void
    {
        $user = $this->createTestUser(['ROLE_USER', 'ROLE_ADMIN']);
        $this->client->loginUser($user);

        $this->seeUserHasRole('ROLE_ADMIN');
    }

    public function testSeeUserHasRoles(): void
    {
        $user = $this->createTestUser(['ROLE_USER', 'ROLE_CUSTOMER']);
        $this->client->loginUser($user);

        $this->seeUserHasRoles(['ROLE_USER', 'ROLE_CUSTOMER']);
    }

    public function testSeeUserPasswordDoesNotNeedRehash(): void
    {
        $user = $this->createTestUser(['ROLE_USER']);
        $this->client->loginUser($user);

        $this->seeUserPasswordDoesNotNeedRehash();
    }

    private function createTestUser(array $roles): \TestUser
    {
        $hasher = $this->grabService('security.password_hasher');
        $hashed = $hasher->hashPassword(new \TestUser('tmp', ''), '123456');

        return new \TestUser('john_doe@gmail.com', $hashed, $roles);
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
