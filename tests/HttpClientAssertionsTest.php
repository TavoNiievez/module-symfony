<?php

namespace Tests;

require_once __DIR__ . '/_app/TestKernel.php';

use Codeception\Module\Symfony\DataCollectorName;
use Codeception\Module\Symfony\HttpClientAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class HttpClientAssertionsTest extends KernelTestCase
{
    use HttpClientAssertionsTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->client = new KernelBrowser(self::$kernel);
        $this->client->enableProfiler();
        $this->client->request('GET', '/http-client');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    protected static function getKernelClass(): string
    {
        return \Tests\_app\TestKernel::class;
    }

    protected function getClient(): KernelBrowser
    {
        return $this->client;
    }

    protected function grabService(string $serviceId): object
    {
        return self::getContainer()->get($serviceId);
    }

    protected function unpersistService(string $serviceName): void
    {
        // no-op for tests
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

    protected function grabCollector(DataCollectorName $name, string $function): DataCollectorInterface
    {
        $profile = $this->client->getProfile();
        if (!$profile) {
            /** @var Profiler $profiler */
            $profiler = self::getContainer()->get('profiler');
            $profile = $profiler->collect($this->client->getRequest(), $this->client->getResponse());
        }

        return $profile->getCollector($name->value);
    }
}
