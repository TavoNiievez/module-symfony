<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class TwigCest
{
    public function twigAssertions(FunctionalTester $I): void
    {
        $I->amOnRoute('twig');
        $I->seeRenderedTemplate('home.html.twig');
        $I->seeRenderedTemplate('layout.html.twig');
        $I->dontSeeRenderedTemplate('other.html.twig');
        $I->seeCurrentTemplateIs('home.html.twig');
    }
}
