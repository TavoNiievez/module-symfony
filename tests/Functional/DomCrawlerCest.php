<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class DomCrawlerCest
{
    public function testDomCrawlerAssertions(FunctionalTester $I): void
    {
        $I->amOnPage('/sample');

        $I->assertCheckboxChecked('agree');
        $I->assertCheckboxNotChecked('subscribe');
        $I->assertInputValueSame('username', 'john');
        $I->assertInputValueNotSame('username', 'doe');
        $I->assertPageTitleContains('Test');
        $I->assertPageTitleSame('Test Page');
        $I->assertSelectorExists('#greeting');
        $I->assertSelectorNotExists('#missing');
        $I->assertSelectorTextContains('#greeting', 'Hello');
        $I->assertSelectorTextNotContains('#greeting', 'Bye');
        $I->assertSelectorTextSame('#greeting', 'Hello World');
    }
}
