<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class HttpClientCest
{
    public function httpClientAssertions(FunctionalTester $I): void
    {
        $I->amOnPage('/http-client');
        $I->assertHttpClientRequest('https://example.com/default', 'GET', null, ['X-Test' => 'yes'], 'app.http_client');
        $I->assertHttpClientRequest('https://example.com/body', 'POST', ['example' => 'payload'], [], 'app.http_client');
        $I->assertHttpClientRequest('https://api.example.com/resource', 'GET', null, [], 'app.http_client.json_client');
        $I->assertHttpClientRequestCount(2, 'app.http_client');
        $I->assertHttpClientRequestCount(1, 'app.http_client.json_client');
        $I->assertNotHttpClientRequest('https://example.com/missing', 'GET', 'app.http_client');
    }
}
