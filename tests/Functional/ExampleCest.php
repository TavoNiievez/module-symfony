<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class ExampleCest
{
    public function seeIndexPage(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
        $I->see('OK');
    }
}
