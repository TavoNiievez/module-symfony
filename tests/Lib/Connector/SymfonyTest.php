<?php

declare(strict_types=1);

namespace Tests\Lib\Connector;

use Codeception\Lib\Connector\Symfony;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;

#[AllowMockObjectsWithoutExpectations]
final class SymfonyTest extends TestCase
{
    private function createConnectorWithoutConstructorSideEffects(Kernel $kernel): Symfony
    {
        $reflection = new \ReflectionClass(Symfony::class);
        $connector = $reflection->newInstanceWithoutConstructor();

        $kernelProperty = new \ReflectionProperty(Symfony::class, 'kernel');
        $kernelProperty->setAccessible(true);
        $kernelProperty->setValue($connector, $kernel);

        $persistentServicesProperty = new \ReflectionProperty(Symfony::class, 'persistentServices');
        $persistentServicesProperty->setAccessible(true);
        $persistentServicesProperty->setValue($connector, []);

        $container = $kernel->getContainer();
        $containerProperty = new \ReflectionProperty(Symfony::class, 'container');
        $containerProperty->setAccessible(true);
        $containerProperty->setValue($connector, $container);

        return $connector;
    }

    public function testRebootKernel(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->expects($this->any())->method('get')->willReturn($container);

        $kernel = $this->createMock(Kernel::class);
        $kernel->method('getContainer')->willReturn($container);

        $kernel->expects($this->exactly(1))->method('shutdown');
        $kernel->expects($this->exactly(2))->method('boot');

        $connector = $this->createConnectorWithoutConstructorSideEffects($kernel);
        $connector->rebootKernel();
    }
}
