<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;

use function array_unique;
use function array_values;

trait CacheTrait
{
    /** @var ContainerInterface|null */
    private ?ContainerInterface $cachedContainer = null;

    /** @var ContainerInterface|null */
    private ?ContainerInterface $cachedTestContainer = null;

    /** @var Profile|null */
    private ?Profile $cachedProfile = null;

    /** @var object|null */
    private ?object $cachedProfileResponse = null;

    /** @var list<non-empty-string> */
    private array $internalDomainsCache = [];

    public function _getContainer(): ContainerInterface
    {
        $currentContainer = $this->kernel->getContainer();

        if ($this->cachedContainer === $currentContainer && $this->cachedTestContainer !== null) {
            return $this->cachedTestContainer;
        }

        $this->cachedContainer = $currentContainer;

        /** @var ContainerInterface $testContainer */
        $testContainer = $currentContainer->has('test.service_container') ? $currentContainer->get('test.service_container') : $currentContainer;
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
        $this->cachedProfile = $profile;
        $this->cachedProfileResponse = $response;
    }

    /** @return list<non-empty-string> */
    protected function getInternalDomains(): array
    {
        if ($this->internalDomainsCache !== []) {
            return $this->internalDomainsCache;
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

        $this->internalDomainsCache = array_values(array_unique($domains));

        return $this->internalDomainsCache;
    }
}
