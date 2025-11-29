<?php

namespace Tests;

use Codeception\Module\Symfony\FormAssertionsTrait;
use Codeception\Module\Symfony\DataCollectorName;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class FormAssertionsTest extends KernelTestCase
{
    use FormAssertionsTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::bootKernel(['debug' => true]);
        $this->client = new KernelBrowser(self::$kernel);
        $this->client->enableProfiler();
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
        return \Tests\_app\TestKernel::class;
    }

    protected function grabCollector(DataCollectorName $name, string $function): DataCollectorInterface
    {
        $profile = $this->client->getProfile();
        if ($profile === false) {
            /** @var Profiler $profiler */
            $profiler = self::getContainer()->get('profiler');
            $profile  = $profiler->collect($this->client->getRequest(), $this->client->getResponse());
        }

        return $profile->getCollector($name->value);
    }

    protected function _getContainer(): ContainerInterface
    {
        return self::getContainer();
    }

    protected function grabService(string $serviceId): object
    {
        return self::getContainer()->get($serviceId);
    }

    public function testFormValues(): void
    {
        $this->assertFormValue('#testForm', 'field1', 'value1');
        $this->assertNoFormValue('#testForm', 'missing_field');
    }

    public function testFormErrorAssertions(): void
    {
        $this->client->request('POST', '/form', [
            'registration_form' => [
                'email' => 'not-an-email',
                'password' => '',
            ],
        ]);

        $this->seeFormHasErrors();
        $this->seeFormErrorMessage('email', 'valid email address');
        $this->seeFormErrorMessages([
            'email' => 'valid email address',
            'password' => 'not be blank',
        ]);
    }

    public function testFormWithoutErrors(): void
    {
        $this->client->request('POST', '/form', [
            'registration_form' => [
                'email' => 'john@example.com',
                'password' => 'top-secret',
            ],
        ]);

        $this->dontSeeFormErrors();
    }
}
