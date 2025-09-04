<?php

namespace Tests;

use Codeception\Module\Symfony\ServicesAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ServicesAssertionsTest extends KernelTestCase
{
    use ServicesAssertionsTrait;

    protected array $persistentServices = [];
    protected array $permanentServices = [];

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

    protected function _getContainer(): ContainerInterface
    {
        return self::getContainer();
    }

    protected function getClient(): KernelBrowser
    {
        return $this->client;
    }

    public function testServicesAssertions(): void
    {
        $this->grabService('router');
        $this->persistService('router');
        $this->assertArrayHasKey('router', $this->persistentServices);

        $this->persistPermanentService('router');
        $this->assertArrayHasKey('router', $this->permanentServices);

        $this->unpersistService('router');
        $this->assertArrayNotHasKey('router', $this->persistentServices);
        $this->assertArrayNotHasKey('router', $this->permanentServices);
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
