<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\SecurityAssertionsTrait;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\SecurityBundle\Security;
use Tests\Support\KernelTestCase;

class SecurityAssertionsTest extends KernelTestCase
{
    use SecurityAssertionsTrait;

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

    private function createTestUser(array $roles): \Tests\_app\Entity\User
    {
        $hasher = $this->grabPasswordHasherService();
        $hashed = $hasher->hashPassword(\Tests\_app\Entity\User::create('tmp', ''), '123456');

        return \Tests\_app\Entity\User::create('john_doe@gmail.com', $hashed, $roles);
    }
}
