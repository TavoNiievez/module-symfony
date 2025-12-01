<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\SecurityAssertionsTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\BrowserKit\Cookie;
use Tests\_app\Entity\User;
use Tests\Support\KernelTestCase;

final class SecurityAssertionsTest extends KernelTestCase
{
    use SecurityAssertionsTrait;

    protected function grabSecurityService(): Security
    {
        return new Security($this->_getContainer());
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

    private function createTestUser(array $roles): User
    {
        $hasher = $this->grabPasswordHasherService();
        $hashed = $hasher->hashPassword(User::create('tmp', ''), '123456');

        return User::create('john_doe@gmail.com', $hashed, $roles);
    }
}
