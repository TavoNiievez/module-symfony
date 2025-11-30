<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\FormAssertionsTrait;
use Codeception\Module\Symfony\DataCollectorName;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Tests\Support\KernelTestCase;

class FormAssertionsTest extends KernelTestCase
{
    use FormAssertionsTrait;

    protected function setUp(): void
    {
        static::bootKernel(['debug' => true]);
        $this->client = new \Symfony\Bundle\FrameworkBundle\KernelBrowser(self::$kernel);
        $this->client->enableProfiler();
        $this->client->request('GET', '/sample');
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

    public function testFormValues(): void
    {
        $this->assertFormValue('#testForm', 'field1', 'value1');
        $this->assertNoFormValue('#testForm', 'missing_field');
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
