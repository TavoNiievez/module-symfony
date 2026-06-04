<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Lib\Di;
use Codeception\Lib\ModuleContainer;
use Codeception\Module\Symfony;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

#[AllowMockObjectsWithoutExpectations]
final class SymfonyTest extends TestCase
{
    public function testGetEntityManagerRetrievesFromContainerAndPersistsServices(): void
    {
        $di = new Di();
        $moduleContainer = new ModuleContainer($di, []);
        $module = new class($moduleContainer) extends Symfony {
            public Container $containerMock;

            public function _getContainer(): Container
            {
                return $this->containerMock;
            }

            /**
             * @return array<non-empty-string, object>
             */
            public function getPermanentServices(): array
            {
                return $this->permanentServices;
            }
        };

        $emMock = $this->createMock(EntityManagerInterface::class);
        $doctrineMock = new \stdClass();
        $defaultEmMock = new \stdClass();
        $defaultConnectionMock = new \stdClass();

        $container = new Container();
        $container->set('doctrine.orm.entity_manager', $emMock);
        $container->set('doctrine', $doctrineMock);
        $container->set('doctrine.orm.default_entity_manager', $defaultEmMock);
        $container->set('doctrine.dbal.default_connection', $defaultConnectionMock);

        $module->containerMock = $container;

        $em = $module->_getEntityManager();

        $this->assertSame($emMock, $em);

        $permanentServices = $module->getPermanentServices();
        $this->assertArrayHasKey('doctrine.orm.entity_manager', $permanentServices);
        $this->assertArrayHasKey('doctrine', $permanentServices);
        $this->assertArrayHasKey('doctrine.orm.default_entity_manager', $permanentServices);
        $this->assertArrayHasKey('doctrine.dbal.default_connection', $permanentServices);
    }

    public function testGetEntityManagerReturnsExistingServiceFromPermanentServices(): void
    {
        $di = new Di();
        $moduleContainer = new ModuleContainer($di, []);
        $module = new class($moduleContainer) extends Symfony {
            /**
             * @param non-empty-string $name
             */
            public function setPermanentService(string $name, object $service): void
            {
                $this->permanentServices[$name] = $service;
            }
        };

        $emMock = $this->createMock(EntityManagerInterface::class);
        $module->setPermanentService('doctrine.orm.entity_manager', $emMock);

        $em = $module->_getEntityManager();

        $this->assertSame($emMock, $em);
    }

    public function testGetEntityManagerFailsIfServiceNotInstanceOfEntityManagerInterface(): void
    {
        $di = new Di();
        $moduleContainer = new ModuleContainer($di, []);
        $module = new class($moduleContainer) extends Symfony {
            public Container $containerMock;

            public function _getContainer(): Container
            {
                return $this->containerMock;
            }
        };

        $notEmService = new \stdClass();

        $container = new Container();
        $container->set('doctrine.orm.entity_manager', $notEmService);

        $module->containerMock = $container;

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Service "doctrine.orm.entity_manager" is not an instance of EntityManagerInterface.');

        $module->_getEntityManager();
    }
}
