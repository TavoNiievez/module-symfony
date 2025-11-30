<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Lib\Connector\Symfony as SymfonyConnector;
use PHPUnit\Framework\TestCase;
class BrowserKitConnectorTest extends TestCase
{
    public function testRequestReturnsSuccessfulResponse(): void
    {
        $kernel = new \Tests\_app\TestKernel('test', true);
        $kernel->boot();
        $browser = new SymfonyConnector($kernel);

        $browser->request('GET', '/');

        $this->assertSame(200, $browser->getResponse()->getStatusCode());
        $this->assertSame('Hello World!', $browser->getResponse()->getContent());
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
