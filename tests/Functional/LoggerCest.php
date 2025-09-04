<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class LoggerCest
{
    public function noDeprecations(FunctionalTester $I): void
    {
        $I->amOnPage('/sample');
        $I->dontSeeDeprecations();
    }
}
