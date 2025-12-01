<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\LoggerAssertionsTrait;
use PHPUnit\Framework\AssertionFailedError;
use Tests\Support\KernelTestCase;

final class LoggerAssertionsTest extends KernelTestCase
{
    use LoggerAssertionsTrait;

    public function testDeprecationsAreReported(): void
    {
        $this->client->request('GET', '/deprecated');
        try {
            $this->dontSeeDeprecations();
            self::fail('Expected deprecations to be reported.');
        } catch (AssertionFailedError $error) {
            $this->assertStringContainsString('deprecation', $error->getMessage());
        }
    }

    public function testDontSeeDeprecations(): void
    {
        $this->client->request('GET', '/sample');
        $this->dontSeeDeprecations();
    }
}
