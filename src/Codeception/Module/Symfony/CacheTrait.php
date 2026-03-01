<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;

use function array_unique;
use function array_values;

trait CacheTrait
{
    private ?ContainerInterface $cachedContainer = null;
    private ?ContainerInterface $cachedTestContainer = null;
    private ?Profile $cachedProfile = null;
    private ?object $cachedProfileResponse = null;

    /** @var list<non-empty-string>|null */
    private ?array $cachedInternalDomains = null;

    public function _getContainer(): ContainerInterface
    {
        $container = $this->kernel->getContainer();

        if ($this->cachedContainer === $container && $this->cachedTestContainer !== null) {
            return $this->cachedTestContainer;
        }

        $this->cachedContainer = $container;
        /** @var ContainerInterface $testContainer */
        $testContainer = $container->has('test.service_container') ? $container->get('test.service_container') : $container;
        $this->cachedTestContainer = $testContainer;

        return $testContainer;
    }

    protected function getProfileFromCache(object $response): ?Profile
    {
        if ($this->cachedProfileResponse === $response && $this->cachedProfile !== null) {
            return $this->cachedProfile;
        }

        return null;
    }

    protected function cacheProfile(object $response, Profile $profile): void
    {
        $this->cachedProfileResponse = $response;
        $this->cachedProfile = $profile;
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
