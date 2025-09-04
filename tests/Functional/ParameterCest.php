<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class ParameterCest
{
    public function grabParameter(FunctionalTester $I): void
    {
        $I->assertSame('value', $I->grabParameter('app.param'));
    }
}
