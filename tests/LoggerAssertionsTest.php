<?php

namespace Tests;

use Codeception\Module\Symfony\LoggerAssertionsTrait;
use Codeception\Module\Symfony\DataCollectorName;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
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
        return \TestKernel::class;
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


    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }

    protected function grabCollector(DataCollectorName $name, string $function): DataCollectorInterface
    {
        /** @var Profiler $profiler */
        $profiler = self::getContainer()->get('profiler');
        $profile = $profiler->collect($this->client->getRequest(), $this->client->getResponse());
        return $profile->getCollector($name->value);
    }
}
