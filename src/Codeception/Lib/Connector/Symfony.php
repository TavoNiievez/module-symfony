<?php

declare(strict_types=1);

namespace Codeception\Lib\Connector;

use InvalidArgumentException;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\TestContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

use function function_exists;

/**
 * @property KernelInterface $kernel
 */
class Symfony extends HttpKernelBrowser
{
    private ContainerInterface $container;
    private bool $hasPerformedRequest = false;

    public function __construct(
        HttpKernelInterface $kernel,
        /** @var array<non-empty-string, object> */
        public array $persistentServices = [],
        private bool $reboot = true
    ) {
        parent::__construct($kernel);
        $this->followRedirects();
        $this->container = $this->resolveContainer();
        $this->rebootKernel();
    }

    protected function doRequest(object $request): Response
    {
        if ($this->reboot) {
            $this->hasPerformedRequest ? $this->rebootKernel() : $this->hasPerformedRequest = true;
        }

        return parent::doRequest($request);
    }

    /**
     * Reboots the kernel.
     *
     * Services from the list of persistent services
     * are updated from service container before kernel shutdown
     * and injected into newly initialized container after kernel boot.
     */
    public function rebootKernel(): void
    {
        foreach ($this->persistentServices as $name => $_) {
            if ($this->container->has($name)) {
                $this->persistentServices[$name] = $this->container->get($name);
            }
        }

        $this->persistDoctrineConnections();

        if ($this->kernel instanceof Kernel) {
            $this->ensureKernelShutdown();
            $this->kernel->boot();
        }

        $this->container = $this->resolveContainer();

        foreach ($this->persistentServices as $name => $service) {
            try {
                $this->container->set($name, $service);
            } catch (InvalidArgumentException $e) {
                if (function_exists('codecept_debug')) {
                    codecept_debug("[Symfony] Can't set persistent service {$name}: {$e->getMessage()}");
                }
            }
        }

        $this->getProfiler()?->enable();
    }

    protected function ensureKernelShutdown(): void
    {
        $this->kernel->boot();
        $this->kernel->shutdown();
    }

    private function resolveContainer(): ContainerInterface
    {
        $container = $this->kernel->getContainer();

        /** @var ContainerInterface $testContainer */
        $testContainer = $container->has('test.service_container') ? $container->get('test.service_container') : $container;

        return $testContainer;
    }

    private function getProfiler(): ?Profiler
    {
        if (!$this->container->has('profiler')) {
            return null;
        }

        $profiler = $this->container->get('profiler');

        return $profiler instanceof Profiler ? $profiler : null;
    }

    private function persistDoctrineConnections(): void
    {
        $container = $this->kernel->getContainer();
        /**
         * @param ContainerInterface&object $container
         */
        $closure = function (ContainerInterface $container): void {
            if (!$container->hasParameter('doctrine.connections')) {
                return;
            }
            /** @var array<string, string> $connections */
            $connections = $container->getParameter('doctrine.connections');
            foreach ($connections as $id) {
                if (property_exists($container, 'services') && is_array($container->services)) {
                    unset($container->services[$id]);
                }
                if (property_exists($container, 'privates') && is_array($container->privates)) {
                    unset($container->privates[$id]);
                }
            }
        };

        $closure->call($container, $container);
    }
}
