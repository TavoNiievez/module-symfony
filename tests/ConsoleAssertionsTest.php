<?php

declare(strict_types=1);

namespace Tests;

use Tests\Support\CodeceptTestCase;
use Codeception\Module\Symfony\ConsoleAssertionsTrait;

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
}
