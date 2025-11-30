<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\DataCollectorName;
use Codeception\Module\Symfony\TwigAssertionsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Tests\Support\KernelTestCase;

class TwigAssertionsTest extends KernelTestCase
{
    use TwigAssertionsTrait;

    protected array $kernelOptions = ['debug' => true];
    protected bool $profilerEnabled = true;

    public function testDontSeeRenderedTemplate(): void
    {
        $this->client->request('GET', '/register');

        $this->dontSeeRenderedTemplate('security/login.html.twig');
    }

    public function testSeeCurrentTemplateIs(): void
    {
        $this->client->request('GET', '/login');

        $this->seeCurrentTemplateIs('security/login.html.twig');
    }

    public function testSeeRenderedTemplate(): void
    {
        $this->client->request('GET', '/login');

        $this->seeRenderedTemplate('layout.html.twig');
        $this->seeRenderedTemplate('security/login.html.twig');
    }

    protected function grabCollector(DataCollectorName $name, string $function): DataCollectorInterface
    {
        /** @var Profiler $profiler */
        $profiler = self::getContainer()->get('profiler');
        $profile = $this->client->getProfile() ?? $profiler->collect($this->client->getRequest(), $this->client->getResponse());

        return $profile->getCollector($name->value);
    }
}
