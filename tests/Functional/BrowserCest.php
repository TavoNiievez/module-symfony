<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class BrowserCest
{
    public function testBrowserAssertions(FunctionalTester $I): void
    {
        $I->setCookie('browser_cookie', 'value');
        $I->amOnPage('/sample');

        $I->assertBrowserHasCookie('browser_cookie');
        $I->assertBrowserCookieValueSame('browser_cookie', 'value');
        $I->assertBrowserNotHasCookie('missing_cookie');

        $I->assertRequestAttributeValueSame('foo', 'bar');

        $I->assertResponseHasCookie('response_cookie');
        $I->assertResponseCookieValueSame('response_cookie', 'yum');
        $I->assertResponseNotHasCookie('other_cookie');

        $I->assertResponseHasHeader('X-Test');
        $I->assertResponseHeaderSame('X-Test', '1');
        $I->assertResponseHeaderNotSame('X-Test', '2');
        $I->assertResponseNotHasHeader('X-None');

        $I->assertResponseFormatSame('html');
        $I->assertResponseIsSuccessful();
        $I->assertResponseStatusCodeSame(200);
        $I->assertRouteSame('sample');

        $I->seePageIsAvailable('/sample');
        $I->seePageRedirectsTo('/redirect', '/sample');

        $I->amOnPage('/unprocessable');
        $I->assertResponseIsUnprocessable();
    }
}
