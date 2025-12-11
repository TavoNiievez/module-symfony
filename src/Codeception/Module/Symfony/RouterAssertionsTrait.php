<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use PHPUnit\Framework\Assert;
use Symfony\Component\Routing\RouterInterface;

use function array_intersect_assoc;
use function is_string;
use function parse_url;
use function sprintf;
use function str_ends_with;

trait RouterAssertionsTrait
{
    /**
     * Opens web page by action name
     *
     * ```php
     * <?php
     * $I->amOnAction('PostController::index');
     * $I->amOnAction('HomeController');
     * $I->amOnAction('ArticleController', ['slug' => 'lorem-ipsum']);
     * ```
     *
     * @param array<string, mixed> $params
     */
    public function amOnAction(string $action, array $params = []): void
    {
        $this->openRoute($this->findRouteByActionOrFail($action), $params);
    }

    /**
     * Opens web page using route name and parameters.
     *
     * ```php
     * <?php
     * $I->amOnRoute('posts.create');
     * $I->amOnRoute('posts.show', ['id' => 34]);
     * ```
     *
     * @param array<string, mixed> $params
     */
    public function amOnRoute(string $routeName, array $params = []): void
    {
        $this->assertRouteExists($routeName);
        $this->openRoute($routeName, $params);
    }

    /**
     * Invalidate previously cached routes.
     */
    public function invalidateCachedRouter(): void
    {
        $this->unpersistService('router');
    }

    /**
     * Checks that current page matches action
     *
     * ```php
     * <?php
     * $I->seeCurrentActionIs('PostController::index');
     * $I->seeCurrentActionIs('HomeController');
     * ```
     */
    public function seeCurrentActionIs(string $action): void
    {
        if ($action === '') {
            Assert::fail('Action cannot be empty');
        }

        $this->findRouteByActionOrFail($action);

        $current = $this->getClient()->getRequest()->attributes->get('_controller');
        if (!is_string($current)) {
            Assert::fail('Current action (controller) is not a string.');
        }

        $this->assertStringEndsWith($action, $current, "Current action is '{$current}'.");
    }

    /**
     * Checks that current url matches route.
     *
     * ```php
     * <?php
     * $I->seeCurrentRouteIs('posts.index');
     * $I->seeCurrentRouteIs('posts.show', ['id' => 8]);
     * ```
     *
     * @param array<string, mixed> $params
     */
    public function seeCurrentRouteIs(string $routeName, array $params = []): void
    {
        $match = $this->getCurrentRouteMatch($routeName);
        $expected = ['_route' => $routeName] + $params;
        $this->assertSame($expected, array_intersect_assoc($expected, $match));
    }

    /**
     * Checks that current url matches route.
     * Unlike seeCurrentRouteIs, this can match without exact route parameters
     *
     * ```php
     * <?php
     * $I->seeInCurrentRoute('my_blog_pages');
     * ```
     */
    public function seeInCurrentRoute(string $routeName): void
    {
        $this->assertSame($routeName, $this->getCurrentRouteMatch($routeName)['_route']);
    }

    /** @return array<string, mixed> */
    private function getCurrentRouteMatch(string $routeName): array
    {
        $this->assertRouteExists($routeName);
        return $this->grabRouterService()->match((string) parse_url($this->getClient()->getRequest()->getRequestUri(), PHP_URL_PATH));
    }

    private function findRouteByActionOrFail(string $action): string
    {
        foreach ($this->grabRouterService()->getRouteCollection()->all() as $name => $route) {
            $ctrl = $route->getDefault('_controller');
            if (is_string($ctrl) && str_ends_with($ctrl, $action)) {
                return $name;
            }
        }
        Assert::fail(sprintf("Action '%s' does not exist.", $action));
    }

    private function assertRouteExists(string $routeName): void
    {
        $this->assertNotNull(
            $this->grabRouterService()->getRouteCollection()->get($routeName),
            sprintf('Route "%s" does not exist.', $routeName)
        );
    }

    /** @param array<string, mixed> $params */
    private function openRoute(string $routeName, array $params = []): void
    {
        $this->getClient()->request('GET', $this->grabRouterService()->generate($routeName, $params));
    }

    protected function grabRouterService(): RouterInterface
    {
        /** @var RouterInterface */
        return $this->grabService('router');
    }
}
