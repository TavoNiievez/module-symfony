<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class TwigCest
{
    public function twigAssertions(FunctionalTester $I): void
    {
        $I->amOnPage('/register');
        $I->dontSeeRenderedTemplate('security/login.html.twig');

        $I->amOnPage('/login');
        $I->seeRenderedTemplate('layout.html.twig');
        $I->seeRenderedTemplate('security/login.html.twig');
        $I->seeCurrentTemplateIs('security/login.html.twig');
    }
}
