<?php

declare(strict_types=1);

namespace Tests\Support;

use Codeception\Module\Symfony\DataCollectorName;
use Codeception\Module\Symfony\ServicesAssertionsTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Tests\_app\Doctrine\TestDatabaseSetup;
use Tests\_app\TestKernel;

abstract class KernelTestCase extends TestCase
{
    use ServicesAssertionsTrait;

    protected KernelBrowser $client;
    protected TestKernel $kernel;
    protected bool $profilerEnabled = false;

    /** @var array<string, object> */
    protected array $persistentServices = [];

    /** @var array<string, object> */
    protected array $permanentServices = [];

    protected function setUp(): void
    {
        $this->kernel = new TestKernel('test', true);
        $this->kernel->boot();

        /** @var EntityManagerInterface $em */
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

    protected function _getContainer(): ContainerInterface
    {
        $container = $this->kernel->getContainer();
        if ($container->has('test.service_container')) {
            $container = $container->get('test.service_container');
        }
        return $container;
    }

    protected function grabCollector(DataCollectorName $name): DataCollectorInterface
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
