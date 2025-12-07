<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use Codeception\Lib\Connector\Symfony as SymfonyConnector;
use PHPUnit\Framework\Assert;

trait ServicesAssertionsTrait
{
    /**
     * Services that should be persistent during test execution between kernel reboots
     *
     * @var array<non-empty-string, object>
     */
    protected array $persistentServices = [];

    /**
     * Services that should be persistent permanently for all tests
     *
     * @var array<non-empty-string, object>
     */
    protected array $permanentServices = [];

    /**
     * Grabs a service from the Symfony dependency injection container (DIC).
     * In the "test" environment, Symfony uses a special `test.service_container`.
     * See the "[Public Versus Private Services](https://symfony.com/doc/current/service_container/alias_private.html#marking-services-as-public-private)" documentation.
     * Services that aren't injected anywhere in your app, need to be defined as `public` to be accessible by Codeception.
     *
     * ```php
     * <?php
     * $em = $I->grabService('doctrine');
     * ```
     *
     * @part services
     * @template T of object
     * @param string|class-string<T> $serviceId
     * @return ($serviceId is class-string<T> ? T : object)
     */
    public function grabService(string $serviceId): object
    {
        $container = $this->_getContainer();

        try {
            return $container->get($serviceId);
        } catch (\Exception $e) {
            if ($container->has($serviceId)) {
                throw $e;
            }

            Assert::fail(
                "Service `{$serviceId}` is required by Codeception, but not loaded by Symfony. Possible solutions:\n
            In your `config/packages/framework.php`/`.yaml`, set `test` to `true` (when in test environment), see https://symfony.com/doc/current/reference/configuration/framework.html#test\n
            If you're still getting this message, you're not using that service in your app, so Symfony isn't loading it at all.\n
            Solution: Set it to `public` in your `config/services.php`/`.yaml`, see https://symfony.com/doc/current/service_container/alias_private.html#marking-services-as-public-private\n"
            );
        }
    }

    /**
     * Get service $serviceName and add it to the lists of persistent services.
     *
     * @part services
     * @param non-empty-string $serviceName
     */
    public function persistService(string $serviceName): void
    {
        $service = $this->grabService($serviceName);
        $this->persistentServices[$serviceName] = $service;
        $this->updateClientPersistentService($serviceName, $service);
    }

    /**
     * Get service $serviceName and add it to the lists of persistent services,
     * making that service persistent between tests.
     *
     * @part services
     * @param non-empty-string $serviceName
     */
    public function persistPermanentService(string $serviceName): void
    {
        $service = $this->grabService($serviceName);
        $this->persistentServices[$serviceName] = $service;
        $this->permanentServices[$serviceName] = $service;
        $this->updateClientPersistentService($serviceName, $service);
    }

    /**
     * Remove service $serviceName from the lists of persistent services.
     *
     * @part services
     * @param non-empty-string $serviceName
     */
    public function unpersistService(string $serviceName): void
    {
        unset($this->persistentServices[$serviceName]);
        unset($this->permanentServices[$serviceName]);

        $this->updateClientPersistentService($serviceName, null);
    }

    /** @param non-empty-string $name */
    protected function updateClientPersistentService(string $name, ?object $service): void {}

    protected function getService(string $serviceId): ?object
    {
        $container = $this->_getContainer();
        if (!$container->has($serviceId)) {
            return null;
        }

        return $container->get($serviceId);
    }
}
