<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\DataCollectorName;
use Codeception\Module\Symfony\HttpClientAssertionsTrait;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Tests\Support\KernelTestCase;

class HttpClientAssertionsTest extends KernelTestCase
{
    use HttpClientAssertionsTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->enableProfiler();
        $this->client->request('GET', '/http-client');
    }

    public function testHttpClientAssertionsAcrossClients(): void
    {
        if (\Symfony\Component\HttpKernel\Kernel::VERSION_ID < 60000) {
            $this->markTestSkipped('HttpClient data collection is not reliable in this test environment for Symfony 5.4');
        }

        $this->assertHttpClientRequest('https://example.com/default', 'GET', null, ['X-Test' => 'yes'], 'app.http_client');
        $this->assertHttpClientRequest('https://example.com/body', 'POST', ['example' => 'payload'], [], 'app.http_client');
        $this->assertHttpClientRequest('https://api.example.com/resource', 'GET', null, [], 'app.http_client.json_client');
        $this->assertHttpClientRequestCount(2, 'app.http_client');
        $this->assertHttpClientRequestCount(1, 'app.http_client.json_client');
        $this->assertNotHttpClientRequest('https://example.com/missing', 'GET', 'app.http_client');
    }
}
