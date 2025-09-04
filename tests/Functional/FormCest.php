<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class FormCest
{
    public function testFormAssertions(FunctionalTester $I): void
    {
        $I->amOnPage('/sample');

        $I->assertFormValue('#testForm', 'field1', 'value1');
        $I->assertNoFormValue('#testForm', 'missing_field');
    }
}
