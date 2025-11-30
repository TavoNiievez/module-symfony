<?php

declare(strict_types=1);

namespace Tests\Support;

use Codeception\Module\Symfony\DataCollectorName;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Tests\_app\Doctrine\TestDatabaseSetup;
use Tests\_app\TestKernel;

abstract class KernelTestCase extends TestCase
{
    protected KernelBrowser $client;
    protected TestKernel $kernel;
    protected bool $profilerEnabled = false;

    protected function setUp(): void
    {
        $this->kernel = new TestKernel('test', true);
        $this->kernel->boot();

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $this->_getContainer()->get('doctrine.orm.entity_manager');
        TestDatabaseSetup::init($em);

        $this->client = new KernelBrowser($this->kernel);

        if ($this->profilerEnabled) {
            $this->client->enableProfiler();
        }
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

    protected function getService(string $serviceId): ?object
    {
        $container = $this->_getContainer();
        if ($container->has($serviceId)) {
            return $container->get($serviceId);
        }
        return null;
    }

    protected function grabService(string $serviceId): object
    {
        $service = $this->getService($serviceId);
        if ($service === null) {
            throw new \RuntimeException("Service '$serviceId' not found.");
        }
        return $service;
    }

    protected function _getContainer(): ContainerInterface
    {
        $container = $this->kernel->getContainer();
        if ($container->has('test.service_container')) {
            $container = $container->get('test.service_container');
        }
        return $container;
    }

    protected function grabCollector(DataCollectorName $name, string $function): DataCollectorInterface
    {
        $profile = $this->client->getProfile();
        if (!$profile) {
            /** @var Profiler $profiler */
            $profiler = $this->_getContainer()->get('profiler');
            $profile = $profiler->collect($this->client->getRequest(), $this->client->getResponse());
        }

        return $profile->getCollector($name->value);
    }
}
