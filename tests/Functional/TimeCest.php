<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class TimeCest
{
    public function timeAssertions(FunctionalTester $I): void
    {
        $I->amOnRoute('sample');
        $I->seeRequestTimeIsLessThan(500);
    }
}
