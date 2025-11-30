<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

abstract class ManualKernelTestCase extends TestCase
{
    protected KernelBrowser $client;
    protected \Tests\_app\TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new \Tests\_app\TestKernel('test', true);
        $this->kernel->boot();
        $this->client = new KernelBrowser($this->kernel);
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
        parent::tearDown();
    }

    protected function getClient(): KernelBrowser
    {
        return $this->client;
    }

    protected function getService(string $serviceId): object
    {
        $container = $this->kernel->getContainer();
        if ($container->has('test.service_container')) {
            $container = $container->get('test.service_container');
        }
        return $container->get($serviceId);
    }
}
