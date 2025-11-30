<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\LoggerAssertionsTrait;
use Codeception\Module\Symfony\DataCollectorName;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Tests\Support\KernelTestCase;

class LoggerAssertionsTest extends KernelTestCase
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
