<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class RouterCest
{
    public function routerAssertions(FunctionalTester $I): void
    {
        $I->amOnRoute('sample');
        $I->seeCurrentRouteIs('sample');
        $I->seeInCurrentRoute('sample');
        $I->seeCurrentActionIs('TestKernel::sample');

        $I->amOnAction('TestKernel::index');
        $I->seeCurrentRouteIs('index');
        $I->invalidateCachedRouter();
    }
}
