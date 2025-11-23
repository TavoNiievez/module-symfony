<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class RouterCest
{
    public function amOnAction(FunctionalTester $I): void
    {
        $I->amOnAction('TestKernel::index');
        $I->see('Hello World!');
    }

    public function amOnRoute(FunctionalTester $I): void
    {
        $I->amOnRoute('index');
        $I->see('Hello World!');
    }

    public function seeCurrentActionIs(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->seeCurrentActionIs('TestKernel::index');
    }

    public function seeCurrentRouteIs(FunctionalTester $I): void
    {
        $I->amOnPage('/login');
        $I->seeCurrentRouteIs('app_login');
    }

    public function seeInCurrentRoute(FunctionalTester $I): void
    {
        $I->amOnPage('/register');
        $I->seeInCurrentRoute('app_register');
    }

    public function invalidateRouterCache(FunctionalTester $I): void
    {
        $I->amOnRoute('index');
        $I->invalidateCachedRouter();
    }
}
