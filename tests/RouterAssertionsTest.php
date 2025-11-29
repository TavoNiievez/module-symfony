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
        return \Tests\_app\TestKernel::class;
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

    public function testAmOnAction(): void
    {
        $this->amOnAction(\Tests\_app\Controller\AppController::class . '::index');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Hello World!', $this->client->getResponse()->getContent());
    }

    public function testAmOnRoute(): void
    {
        $this->amOnRoute('index');

        $this->assertStringContainsString('Hello World!', $this->client->getResponse()->getContent());
    }

    public function testSeeCurrentActionIs(): void
    {
        $this->client->request('GET', '/');

        $this->seeCurrentActionIs(\Tests\_app\Controller\AppController::class . '::index');
    }

    public function testSeeCurrentRouteIs(): void
    {
        $this->client->request('GET', '/login');

        $this->seeCurrentRouteIs('app_login');
    }

    public function testSeeInCurrentRoute(): void
    {
        $this->client->request('GET', '/register');

        $this->seeInCurrentRoute('app_register');
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
