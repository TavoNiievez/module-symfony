<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use WeakMap;

use function array_unique;
use function array_values;

trait CacheTrait
{
    /** @var WeakMap<object, Profile>|null */
    private ?WeakMap $profileCache = null;

    /**
     * @var array{
     *     internalDomains?: list<non-empty-string>,
     *     messageLoggerServiceId?: ?string,
     *     notifierLoggerServiceId?: ?string
     * }
     */
    protected array $state = [];

    public function _getContainer(): ContainerInterface
    {
        $container = $this->kernel->getContainer();

        /** @var ContainerInterface $testContainer */
        $testContainer = $container->has('test.service_container') ? $container->get('test.service_container') : $container;

        return $testContainer;
    }

    protected function getProfileFromCache(object $response): ?Profile
    {
        return $this->profileCache !== null ? ($this->profileCache[$response] ?? null) : null;
    }

    protected function cacheProfile(object $response, Profile $profile): void
    {
        $this->profileCache ??= new WeakMap();
        $this->profileCache[$response] = $profile;
    }

    /** @return list<non-empty-string> */
    protected function getInternalDomains(): array
    {
        if (isset($this->state['internalDomains'])) {
            return $this->state['internalDomains'];
        }

        $domains = [];
        foreach ($this->grabRouterService()->getRouteCollection() as $route) {
            if ($route->getHost() !== '') {
                $regex = $route->compile()->getHostRegex();
                if ($regex !== null && $regex !== '') {
                    $domains[] = $regex;
                }
            }
        }

        return $this->state['internalDomains'] = array_values(array_unique($domains));
    }

    protected function clearInternalDomainsCache(): void
    {
        unset($this->state['internalDomains']);
    }

    /**
     * @template T of object
     * @param 'messageLoggerServiceId'|'notifierLoggerServiceId' $key
     * @param string[] $serviceIds
     * @param class-string<T> $expectedClass
     * @return T|null
     */
    protected function grabCachedService(string $key, array $serviceIds, string $expectedClass): ?object
    {
        $serviceId = $this->state[$key] ??= (function () use ($serviceIds, $expectedClass): ?string {
            foreach ($serviceIds as $id) {
                if ($this->getService($id) instanceof $expectedClass) {
                    return $id;
                }
            }
            return null;
        })();

        if ($serviceId === null) {
            return null;
        }

        $service = $this->getService($serviceId);
        if ($service instanceof $expectedClass) {
            return $service;
        }

        return null;
    }
}
