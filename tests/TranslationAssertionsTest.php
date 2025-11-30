<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\DataCollectorName;
use Codeception\Module\Symfony\TranslationAssertionsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Tests\Support\KernelTestCase;

class TranslationAssertionsTest extends KernelTestCase
{
    use TranslationAssertionsTrait;

    protected function setUp(): void
    {
        static::bootKernel(['debug' => true]);
        $this->client = new \Symfony\Bundle\FrameworkBundle\KernelBrowser(self::$kernel);
        $this->client->enableProfiler();
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
}
