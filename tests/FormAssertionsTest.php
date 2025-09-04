<?php

namespace Tests;

use Codeception\Module\Symfony\FormAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FormAssertionsTest extends KernelTestCase
{
    use FormAssertionsTrait;

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

    public function testFormAssertions(): void
    {
        $this->assertFormValue('#testForm', 'field1', 'value1');
        $this->assertNoFormValue('#testForm', 'missing_field');
    }
}
