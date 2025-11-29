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
        $this->client->request('GET', '/test_page');
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
        return \Tests\_app\TestKernel::class;
    }

    public function testAssertCheckboxChecked(): void
    {
        $this->assertCheckboxChecked('exampleCheckbox', 'The checkbox should be checked.');
    }

    public function testAssertCheckboxNotChecked(): void
    {
        $this->assertCheckboxNotChecked('nonExistentCheckbox', 'This checkbox should not be checked.');
    }

    public function testAssertInputValueSame(): void
    {
        $this->assertInputValueSame('exampleInput', 'Expected Value', 'The input value should be "Expected Value".');
    }

    public function testAssertPageTitleContains(): void
    {
        $this->assertPageTitleContains('Test', 'The page title should contain "Test".');
    }

    public function testAssertPageTitleSame(): void
    {
        $this->assertPageTitleSame('Test Page', 'The page title should be "Test Page".');
    }

    public function testAssertSelectorExists(): void
    {
        $this->assertSelectorExists('h1', 'The <h1> element should be present.');
    }

    public function testAssertSelectorNotExists(): void
    {
        $this->assertSelectorNotExists('.non-existent-class', 'This selector should not exist.');
    }

    public function testAssertSelectorTextSame(): void
    {
        $this->assertSelectorTextSame('h1', 'Test Page', 'The text in the <h1> tag should be exactly "Test Page".');
    }
}
