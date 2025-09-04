<?php

namespace Tests;

use Codeception\Module\Symfony\ConsoleAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConsoleAssertionsTest extends KernelTestCase
{
    use ConsoleAssertionsTrait;

    protected static function getKernelClass(): string
    {
        return \TestKernel::class;
    }

    protected function grabService(string $serviceId): object
    {
        return self::getContainer()->get($serviceId);
    }

    protected function unpersistService(string $serviceName): void
    {
        // no-op for tests
    }

    public function testRunSymfonyConsoleCommand(): void
    {
        $output = $this->runSymfonyConsoleCommand('app:hello', ['name' => 'Codeception']);
        $this->assertStringContainsString('Hello Codeception', $output);
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
