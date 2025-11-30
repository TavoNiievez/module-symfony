<?php

declare(strict_types=1);

namespace Tests\Support;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as BaseKernelTestCase;

abstract class KernelTestCase extends BaseKernelTestCase
{
    protected KernelBrowser $client;
    protected array $kernelOptions = [];
    protected bool $profilerEnabled = false;

    protected function setUp(): void
    {
        self::bootKernel($this->kernelOptions);
        $this->client = new KernelBrowser(self::$kernel);

        if ($this->profilerEnabled) {
            $this->client->enableProfiler();
        }
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

    protected function getService(string $serviceId): ?object
    {
        return $this->grabService($serviceId);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
