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
        self::bootKernel();
        $this->client = new KernelBrowser(self::$kernel);
        $this->client->request('GET', '/translation');
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

    public function testTranslationAssertions(): void
    {
        $this->dontSeeMissingTranslations();
        $this->dontSeeFallbackTranslations();
        $this->assertGreaterThanOrEqual(0, $this->grabDefinedTranslationsCount());
        $this->seeAllTranslationsDefined();
        $this->seeDefaultLocaleIs('en');
        $this->seeFallbackLocalesAre(['es']);
        $this->seeFallbackTranslationsCountLessThan(1);
        $this->seeMissingTranslationsCountLessThan(1);
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
