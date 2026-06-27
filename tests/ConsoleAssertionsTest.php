<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\ConsoleAssertionsTrait;
use Tests\Support\CodeceptTestCase;

final class ConsoleAssertionsTest extends CodeceptTestCase
{
    use ConsoleAssertionsTrait;

    public function testRunSymfonyConsoleCommand(): void
    {
        $this->assertStringContainsString('No option', $this->runSymfonyConsoleCommand('app:test-command'));
        $this->assertStringContainsString('Option selected', $this->runSymfonyConsoleCommand('app:test-command', ['--opt' => true]));
        $this->assertStringContainsString('Option selected', $this->runSymfonyConsoleCommand('app:test-command', ['-o' => true]));
        $this->assertSame('', $this->runSymfonyConsoleCommand('app:test-command', ['-q']));
    }

    public function testAssertCommandIsSuccessful(): void
    {
        $result = $this->runCommand('app:result-command');

        $this->assertCommandIsSuccessful($result);
        $this->assertStringContainsString('All good.', $result->getOutput());
    }

    public function testAssertCommandFailed(): void
    {
        $result = $this->runCommand('app:result-command', ['--fail' => true]);

        $this->assertCommandFailed($result);
        $this->assertStringContainsString('Something failed.', $result->getErrorOutput());
    }

    public function testAssertCommandIsInvalid(): void
    {
        $this->assertCommandIsInvalid($this->runCommand('app:result-command', ['--invalid' => true]));
    }

    public function testAssertCommandResultEquals(): void
    {
        $result = $this->runCommand('app:result-command');

        $this->assertCommandResultEquals($result, expectedStatusCode: 0, expectedDisplay: "All good.\n");
    }
}
