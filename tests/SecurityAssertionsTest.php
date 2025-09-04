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

    public function testSecurityAssertions(): void
    {
        $this->dontSeeAuthentication();
        $this->dontSeeRememberedAuthentication();

        $hasher = $this->grabService('security.password_hasher');
        $hashed = $hasher->hashPassword(new \TestUser('tmp', ''), 'password');
        $user = new \TestUser('john@example.com', $hashed, ['ROLE_USER', 'ROLE_ADMIN']);
        $this->getClient()->loginUser($user);

        $this->seeAuthentication();

        $this->getClient()->getCookieJar()->set(new Cookie('REMEMBERME', 'test'));
        $this->seeRememberedAuthentication();

        $this->seeUserHasRole('ROLE_ADMIN');
        $this->seeUserHasRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $this->seeUserPasswordDoesNotNeedRehash();
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
