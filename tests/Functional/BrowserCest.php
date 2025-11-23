<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class BrowserCest
{
    public function assertBrowserCookieValueSame(FunctionalTester $I): void
    {
        $I->setCookie('TESTCOOKIE', 'codecept');
        $I->assertBrowserCookieValueSame('TESTCOOKIE', 'codecept');
    }

    public function assertBrowserHasCookie(FunctionalTester $I): void
    {
        $I->setCookie('TESTCOOKIE', 'codecept');
        $I->assertBrowserHasCookie('TESTCOOKIE');
    }

    public function assertBrowserNotHasCookie(FunctionalTester $I): void
    {
        $I->setCookie('TESTCOOKIE', 'codecept');
        $I->resetCookie('TESTCOOKIE');
        $I->assertBrowserNotHasCookie('TESTCOOKIE');
    }

    public function assertRequestAttributeValueSame(FunctionalTester $I): void
    {
        $I->amOnPage('/request_attr');
        $I->assertRequestAttributeValueSame('page', 'register');
    }

    public function assertResponseCookieValueSame(FunctionalTester $I): void
    {
        $I->amOnPage('/response_cookie');
        $I->assertResponseCookieValueSame('TESTCOOKIE', 'codecept');
    }

    public function assertResponseFormatSame(FunctionalTester $I): void
    {
        $I->amOnPage('/response_json');
        $I->assertResponseFormatSame('json');
    }

    public function assertResponseHasCookie(FunctionalTester $I): void
    {
        $I->amOnPage('/response_cookie');
        $I->assertResponseHasCookie('TESTCOOKIE');
    }

    public function assertResponseHasHeader(FunctionalTester $I): void
    {
        $I->amOnPage('/response_json');
        $I->assertResponseHasHeader('content-type');
    }

    public function assertResponseHeaderNotSame(FunctionalTester $I): void
    {
        $I->amOnPage('/response_json');
        $I->assertResponseHeaderNotSame('content-type', 'application/octet-stream');
    }

    public function assertResponseHeaderSame(FunctionalTester $I): void
    {
        $I->amOnPage('/response_json');
        $I->assertResponseHeaderSame('content-type', 'application/json');
    }

    public function assertResponseIsSuccessful(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->assertResponseIsSuccessful();
    }

    public function assertResponseIsUnprocessable(FunctionalTester $I): void
    {
        $I->amOnPage('/unprocessable_entity');
        $I->assertResponseIsUnprocessable();
    }

    public function assertResponseNotHasCookie(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->assertResponseNotHasCookie('TESTCOOKIE');
    }

    public function assertResponseNotHasHeader(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->assertResponseNotHasHeader('accept-charset');
    }

    public function assertResponseRedirects(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
        $I->amOnPage('/redirect_home');
        $I->assertResponseRedirects();
        $I->assertResponseRedirects('/');
    }

    public function assertResponseStatusCodeSame(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
        $I->amOnPage('/redirect_home');
        $I->assertResponseStatusCodeSame(302);
    }

    public function assertRouteSame(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->assertRouteSame('index');

        $I->amOnPage('/login');
        $I->assertRouteSame('app_login');
    }

    public function seePageIsAvailable(FunctionalTester $I): void
    {
        $I->seePageIsAvailable('/login');

        $I->amOnPage('/register');
        $I->seePageIsAvailable();
    }

    public function seePageRedirectsTo(FunctionalTester $I): void
    {
        $I->seePageRedirectsTo('/dashboard', '/login');
    }

    public function submitSymfonyForm(FunctionalTester $I): void
    {
        $I->registerUser('jane_doe@gmail.com', '123456', followRedirects: false);
        $I->assertResponseRedirects('/dashboard');
    }
}
