<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\ServicesAssertionsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Tests\Support\KernelTestCase;

class ServicesAssertionsTest extends KernelTestCase
{
    use ServicesAssertionsTrait;

    protected array $persistentServices = [];
    protected array $permanentServices = [];

    public function testGrabServiceReturnsSecurityHelper(): void
    {
        $securityHelper = $this->grabService('security.helper');

        $this->assertInstanceOf(Security::class, $securityHelper);
    }

    public function testPersistAndUnpersistService(): void
    {
        $this->persistService('router');
        $this->assertArrayHasKey('router', $this->persistentServices);

        $this->persistPermanentService('router');
        $this->assertArrayHasKey('router', $this->permanentServices);

        $this->unpersistService('router');
        $this->assertArrayNotHasKey('router', $this->persistentServices);
        $this->assertArrayNotHasKey('router', $this->permanentServices);
    }
}
