<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class FormCest
{
    public function testFormValues(FunctionalTester $I): void
    {
        $I->amOnPage('/sample');

        $I->assertFormValue('#testForm', 'field1', 'value1');
        $I->assertNoFormValue('#testForm', 'missing_field');
    }

    public function testFormErrors(FunctionalTester $I): void
    {
        $I->amOnPage('/form');
        $I->submitForm('form[name="registration_form"]', [
            'registration_form[email]' => 'not-an-email',
            'registration_form[password]' => '',
        ]);

        $I->seeFormHasErrors();
        $I->seeFormErrorMessage('email', 'valid email address');
        $I->seeFormErrorMessages([
            'email' => 'valid email address',
            'password' => 'not be blank',
        ]);
    }

    public function testFormWithoutErrors(FunctionalTester $I): void
    {
        $I->amOnPage('/form');
        $I->submitForm('form[name="registration_form"]', [
            'registration_form[email]' => 'john@example.com',
            'registration_form[password]' => 'top-secret',
        ]);

        $I->dontSeeFormErrors();
    }
}
