<?php

namespace Tests;

use Codeception\Module\Symfony\RouterAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RouterAssertionsTest extends KernelTestCase
{
    use RouterAssertionsTrait;

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

    protected function unpersistService(string $serviceName): void
    {
        // no-op for tests
    }

    public function testRouterAssertions(): void
    {
        $this->amOnRoute('sample');
        $this->seeCurrentRouteIs('sample');
        $this->seeInCurrentRoute('sample');
        $this->seeCurrentActionIs('TestKernel::sample');

        $this->amOnAction('TestKernel::index');
        $this->seeCurrentRouteIs('index');

        $this->invalidateCachedRouter();
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
