<?php

declare(strict_types=1);

namespace Tests;

use Tests\Support\KernelTestCase;

final class ConsoleAssertionsTest extends KernelTestCase
{
    public function testRunSymfonyConsoleCommand(): void
    {
        $this->assertStringContainsString('No option', $this->runSymfonyConsoleCommand('app:test-command'));
        $this->assertStringContainsString('Option selected', $this->runSymfonyConsoleCommand('app:test-command', ['--opt' => true]));
        $this->assertStringContainsString('Option selected', $this->runSymfonyConsoleCommand('app:test-command', ['-o' => true]));
        $this->assertSame('', $this->runSymfonyConsoleCommand('app:test-command', ['-q']));
    }
}
