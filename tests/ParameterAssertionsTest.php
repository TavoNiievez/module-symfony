<?php

namespace Tests;

use Codeception\Module\Symfony\ParameterAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParameterAssertionsTest extends KernelTestCase
{
    use ParameterAssertionsTrait;

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

    public function testGrabParameter(): void
    {
        $this->assertSame('value', $this->grabParameter('app.param'));
    }

    public function testGrabBusinessNameParameter(): void
    {
        $this->assertSame('Codeception', $this->grabParameter('app.business_name'));
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
