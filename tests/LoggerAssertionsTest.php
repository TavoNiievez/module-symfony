<?php

namespace Tests;

require_once __DIR__ . '/_app/TestKernel.php';

use Codeception\Module\Symfony\LoggerAssertionsTrait;
use Codeception\Module\Symfony\DataCollectorName;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class LoggerAssertionsTest extends KernelTestCase
{
    use LoggerAssertionsTrait;

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

    protected function unpersistService(string $serviceName): void
    {
        // no-op for tests
    }

    public function testDontSeeDeprecations(): void
    {
        $this->client->request('GET', '/sample');
        $this->dontSeeDeprecations();
    }

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


    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }

    protected function grabCollector(DataCollectorName $name, string $function): DataCollectorInterface
    {
        if ($name === DataCollectorName::LOGGER) {
            $collector = new LoggerDataCollector($this->grabService('logger'));
            $collector->collect($this->client->getRequest(), $this->client->getResponse());
            $collector->lateCollect();

            return $collector;
        }

        /** @var Profiler $profiler */
        $profiler = self::getContainer()->get('profiler');
        $profile = $profiler->collect($this->client->getRequest(), $this->client->getResponse());
        return $profile->getCollector($name->value);
    }
}
