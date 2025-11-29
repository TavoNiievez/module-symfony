<?php

namespace Tests;

use Codeception\Module\Symfony\DataCollectorName;
use Codeception\Module\Symfony\TranslationAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class TranslationAssertionsTest extends KernelTestCase
{
    use TranslationAssertionsTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::bootKernel(['debug' => true]);
        $this->client = new KernelBrowser(self::$kernel);
        $this->client->enableProfiler();
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

    protected function _getContainer(): ContainerInterface
    {
        return self::getContainer();
    }

    public function testDontSeeFallbackTranslations(): void
    {
        $this->client->request('GET', '/register');
        $this->dontSeeFallbackTranslations();
    }

    public function testDontSeeMissingTranslations(): void
    {
        $this->client->request('GET', '/');
        $this->dontSeeMissingTranslations();
    }

    public function testGrabDefinedTranslationsCount(): void
    {
        $this->client->request('GET', '/register');
        $this->assertSame(6, $this->grabDefinedTranslationsCount());
    }

    public function testSeeAllTranslationsDefined(): void
    {
        $this->client->request('GET', '/register');
        $this->seeAllTranslationsDefined();
    }

    public function testSeeDefaultLocaleIs(): void
    {
        $this->client->request('GET', '/register');
        $this->seeDefaultLocaleIs('en');
    }

    public function testSeeFallbackLocalesAre(): void
    {
        $this->client->request('GET', '/register');
        $this->seeFallbackLocalesAre(['es']);
    }

    public function testSeeFallbackTranslationsCountLessThan(): void
    {
        $this->client->request('GET', '/register');
        $this->seeFallbackTranslationsCountLessThan(1);
    }

    public function testSeeMissingTranslationsCountLessThan(): void
    {
        $this->client->request('GET', '/');
        $this->seeMissingTranslationsCountLessThan(1);
    }

    protected function grabCollector(DataCollectorName $name, string $function): DataCollectorInterface
    {
        /** @var Profiler $profiler */
        $profiler = self::getContainer()->get('profiler');
        $profile = $this->client->getProfile() ?? $profiler->collect($this->client->getRequest(), $this->client->getResponse());

        return $profile->getCollector($name->value);
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
