<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\CodeceptTestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class ServicesAssertionsTest extends CodeceptTestCase
{
    public function testGrabService(): void
    {
        $securityHelper = $this->grabService('security.helper');

        $this->assertInstanceOf(Security::class, $securityHelper);
    }

    public function testPersistService(): void
    {
        $this->persistService('router');
        $this->assertArrayHasKey('router', $this->persistentServices);
    }

    public function testPersistPermanentService(): void
    {
        $this->persistPermanentService('router');
        $this->assertArrayHasKey('router', $this->permanentServices);
        $this->assertArrayHasKey('router', $this->persistentServices);
    }

    public function testUnpersistService(): void
    {
        $this->persistService('router');
        $this->unpersistService('router');
        $this->assertArrayNotHasKey('router', $this->persistentServices);
    }
}
