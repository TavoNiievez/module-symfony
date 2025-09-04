<?php

namespace Tests;

use Codeception\Module\Symfony\DataCollectorName;
use Codeception\Module\Symfony\TwigAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class TwigAssertionsTest extends KernelTestCase
{
    use TwigAssertionsTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->client = new KernelBrowser(self::$kernel);
        $this->client->request('GET', '/twig');
    }

    protected static function getKernelClass(): string
    {
        return \TestKernel::class;
    }

    protected function getClient(): KernelBrowser
    {
        return $this->client;
    }

    protected function grabService(string $serviceId): object
    {
        return self::getContainer()->get($serviceId);
    }

    protected function _getContainer(): ContainerInterface
    {
        return self::getContainer();
    }

    public function testTwigAssertions(): void
    {
        $this->seeRenderedTemplate('home.html.twig');
        $this->seeRenderedTemplate('layout.html.twig');
        $this->dontSeeRenderedTemplate('other.html.twig');
        $this->seeCurrentTemplateIs('home.html.twig');
    }

    protected function grabCollector(DataCollectorName $name, string $function): DataCollectorInterface
    {
        /** @var Profiler $profiler */
        $profiler = self::getContainer()->get('profiler');
        $profile = $profiler->collect($this->client->getRequest(), $this->client->getResponse());
        return $profile->getCollector($name->value);
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
