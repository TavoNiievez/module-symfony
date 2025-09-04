<?php

namespace Tests;

use Codeception\Module\Symfony\BrowserAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\Cookie;

class BrowserAssertionsTest extends KernelTestCase
{
    use BrowserAssertionsTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->client = new KernelBrowser(self::$kernel);
        $this->client->getCookieJar()->set(new Cookie('browser_cookie', 'value'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    protected function getClient(): KernelBrowser
    {
        return $this->client;
    }

    protected static function getKernelClass(): string
    {
        return \TestKernel::class;
    }

    public function testBrowserAssertions(): void
    {
        $this->client->request('GET', '/sample');

        $this->assertBrowserHasCookie('browser_cookie');
        $this->assertBrowserCookieValueSame('browser_cookie', 'value');
        $this->assertBrowserNotHasCookie('missing_cookie');

        $this->assertRequestAttributeValueSame('foo', 'bar');

        $this->assertResponseHasCookie('response_cookie');
        $this->assertResponseCookieValueSame('response_cookie', 'yum');
        $this->assertResponseNotHasCookie('other_cookie');

        $this->assertResponseHasHeader('X-Test');
        $this->assertResponseHeaderSame('X-Test', '1');
        $this->assertResponseHeaderNotSame('X-Test', '2');
        $this->assertResponseNotHasHeader('X-None');

        $this->assertResponseFormatSame('html');
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('sample');

        $this->seePageIsAvailable('/sample');
        $this->seePageRedirectsTo('/redirect', '/sample');

        $this->client->request('GET', '/unprocessable');
        $this->assertResponseIsUnprocessable();
    }
}
