<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\ConsoleAssertionsTrait;
use Tests\_app\Command\DoctrineFixturesLoadCommand;
use Tests\Support\KernelTestCase;

class ConsoleAssertionsTest extends KernelTestCase
{
    use ConsoleAssertionsTrait;

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
}
