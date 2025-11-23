<?php

namespace Tests;

use Codeception\Module\Symfony\ConsoleAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\_app\DoctrineFixturesLoadCommand;

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
        $output = $this->runSymfonyConsoleCommand('app:example-command');
        $this->assertStringContainsString('Hello world!', $output);

        $output = $this->runSymfonyConsoleCommand('app:example-command', ['-s' => true]);
        $this->assertStringContainsString('Bye world!', $output);

        $output = $this->runSymfonyConsoleCommand('app:example-command', ['--something' => true]);
        $this->assertStringContainsString('Bye world!', $output);
    }

    public function testRunSymfonyConsoleCommandWithQuietOption(): void
    {
        DoctrineFixturesLoadCommand::reset();

        $output = $this->runSymfonyConsoleCommand('doctrine:fixtures:load', ['-q']);

        $this->assertSame('', $output);
        $this->assertSame(1, DoctrineFixturesLoadCommand::runs());
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
