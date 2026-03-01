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

    /** @var list<non-empty-string>|null */
    private ?array $cachedInternalDomains = null;

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
        if ($this->cachedInternalDomains !== null) {
            return $this->cachedInternalDomains;
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

        $this->cachedInternalDomains = array_values(array_unique($domains));

        return $this->cachedInternalDomains;
    }

    protected function clearInternalDomainsCache(): void
    {
        $this->cachedInternalDomains = null;
    }
}
