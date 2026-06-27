<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

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
        $service = $this->getService($serviceId);

        if ($service !== null) {
            return $service;
        }

        Assert::fail(
            "Service `{$serviceId}` is required by Codeception, but not loaded by Symfony. Possible solutions:\n
            In your `config/packages/framework.php`/`.yaml`, set `test` to `true` (when in test environment), see https://symfony.com/doc/current/reference/configuration/framework.html#test\n
            If you're still getting this message, you're not using that service in your app, so Symfony isn't loading it at all.\n
            Solution: Set it to `public` in your `config/services.php`/`.yaml`, see https://symfony.com/doc/current/service_container/alias_private.html#marking-services-as-public-private\n"
        );
    }

    /**
     * Replaces a service in the container with the given object, for example a mock or a stub.
     * The replacement is injected immediately and re-injected after each kernel reboot,
     * so it stays in effect across requests until [`unmockService()`](#unmockService) is called.
     * Build the double with Codeception `Stub`, PHPUnit, Mockery, or any plain object.
     *
     * The service must not have been initialized yet (Symfony forbids replacing an
     * already instantiated service), so mock it before the request that uses it.
     *
     * ```php
     * <?php
     * $I->mockService(PaymentGateway::class, $this->makeEmpty(PaymentGateway::class));
     * $I->mockService('http_client', new MockHttpClient($responses));
     * $I->mockService('clock', new MockClock('2030-01-01'));
     * ```
     *
     * @part services
     * @param non-empty-string $serviceId
     */
    public function mockService(string $serviceId, object $replacement): void
    {
        $this->persistentServices[$serviceId] = $replacement;
        $this->updateClientPersistentService($serviceId, $replacement);
        $this->_getContainer()->set($serviceId, $replacement);
    }

    /**
     * Get service $serviceName and add it to the lists of persistent services.
     *
     * @part services
     * @param non-empty-string $serviceName
     */
    public function persistService(string $serviceName): void
    {
        $this->doPersistService($serviceName, false);
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
        $this->doPersistService($serviceName, true);
    }

    /**
     * Removes a service replacement set with [`mockService()`](#mockService),
     * restoring the original service on the next request.
     *
     * ```php
     * <?php
     * $I->unmockService('http_client');
     * ```
     *
     * @part services
     * @param non-empty-string $serviceId
     */
    public function unmockService(string $serviceId): void
    {
        unset($this->persistentServices[$serviceId], $this->permanentServices[$serviceId]);
        $this->updateClientPersistentService($serviceId, null);
    }

    /**
     * Remove service $serviceName from the lists of persistent services.
     *
     * @part services
     * @param non-empty-string $serviceName
     */
    public function unpersistService(string $serviceName): void
    {
        unset($this->persistentServices[$serviceName], $this->permanentServices[$serviceName]);
        $this->updateClientPersistentService($serviceName, null);
    }

    /** @param non-empty-string $name */
    protected function updateClientPersistentService(string $name, ?object $service): void {}

    protected function getService(string $serviceId): ?object
    {
        $container = $this->_getContainer();
        return $container->has($serviceId) ? $container->get($serviceId) : null;
    }

    /** @param non-empty-string $name */
    private function doPersistService(string $name, bool $permanent): void
    {
        $service = $this->grabService($name);
        $this->persistentServices[$name] = $service;
        if ($permanent) {
            $this->permanentServices[$name] = $service;
        }
        $this->updateClientPersistentService($name, $service);
    }
}
