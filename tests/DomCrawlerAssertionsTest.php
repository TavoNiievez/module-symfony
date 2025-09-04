<?php

namespace Tests;

use Codeception\Module\Symfony\DomCrawlerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DomCrawlerAssertionsTest extends KernelTestCase
{
    use DomCrawlerAssertionsTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->client = new KernelBrowser(self::$kernel);
        $this->client->request('GET', '/sample');
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

    public function testDomCrawlerAssertions(): void
    {
        $this->assertCheckboxChecked('agree');
        $this->assertCheckboxNotChecked('subscribe');
        $this->assertInputValueSame('username', 'john');
        $this->assertInputValueNotSame('username', 'doe');
        $this->assertPageTitleContains('Test');
        $this->assertPageTitleSame('Test Page');
        $this->assertSelectorExists('#greeting');
        $this->assertSelectorNotExists('#missing');
        $this->assertSelectorTextContains('#greeting', 'Hello');
        $this->assertSelectorTextNotContains('#greeting', 'Bye');
        $this->assertSelectorTextSame('#greeting', 'Hello World');
    }
}
