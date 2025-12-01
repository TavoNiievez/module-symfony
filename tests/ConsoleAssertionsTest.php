<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\CodeceptTestCase;

final class ConsoleAssertionsTest extends CodeceptTestCase
{
    public function testRunSymfonyConsoleCommand(): void
    {
        $output = $this->runSymfonyConsoleCommand('app:test-command');
        $this->assertStringContainsString('No option', $output);

        $output = $this->runSymfonyConsoleCommand('app:test-command', ['--opt' => true]);
        $this->assertStringContainsString('Option selected', $output);

        $output = $this->runSymfonyConsoleCommand('app:test-command', ['-o' => true]);
        $this->assertStringContainsString('Option selected', $output);

        $output = $this->runSymfonyConsoleCommand('app:test-command', ['-q']);
        $this->assertSame('', $output);
    }
}
