<?php

namespace Tests;

use Codeception\Module\Symfony\EventsAssertionsTrait;
use Codeception\Module\Symfony\DataCollectorName;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Tests\_app\Event\NamedEvent;
use Tests\_app\Event\OrphanEvent;
use Tests\_app\Event\SampleEvent;
use Tests\_app\Listener\NamedEventListener;
use Tests\_app\Listener\SampleEventListener;

class EventsAssertionsTest extends KernelTestCase
{
    use EventsAssertionsTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel(['debug' => true]);
        $this->client = new KernelBrowser(self::$kernel);
        $this->client->enableProfiler();
    }

    protected static function getKernelClass(): string
    {
        return \TestKernel::class;
    }

    protected function getClient(): KernelBrowser
    {
        return $this->client;
    }

    protected function grabCollector(DataCollectorName $name, string $function): DataCollectorInterface
    {
        return $this->getProfile()->getCollector($name->value);
    }

    protected function _getContainer(): ContainerInterface
    {
        return self::getContainer();
    }

    protected function grabService(string $serviceId): object
    {
        return self::getContainer()->get($serviceId);
    }

    private function getProfile(): \Symfony\Component\HttpKernel\Profiler\Profile
    {
        if ($this->client->getProfile() !== null) {
            return $this->client->getProfile();
        }

        /** @var Profiler $profiler */
        $profiler = self::getContainer()->get('profiler');

        return $profiler->collect($this->client->getRequest(), $this->client->getResponse());
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }

    public function testEventDispatchingAndListeners(): void
    {
        $this->client->request('GET', '/dispatch-event');

        $this->seeEvent(SampleEvent::class);
        $this->dontSeeEvent(OrphanEvent::class);
        $this->seeEventListenerIsCalled(SampleEventListener::class, SampleEvent::class);
        $this->dontSeeEventListenerIsCalled(NamedEventListener::class, SampleEvent::class);
        $this->dontSeeOrphanEvent();
    }

    public function testNamedEventListenerFiltering(): void
    {
        $this->client->request('GET', '/dispatch-named-event');

        $this->seeEventListenerIsCalled(NamedEventListener::class, 'named.event');
        $this->dontSeeEventListenerIsCalled(SampleEventListener::class, 'named.event');
    }

    public function testOrphanEventDetection(): void
    {
        $this->client->request('GET', '/dispatch-orphan-event');

        $this->seeOrphanEvent(OrphanEvent::class);
        $this->dontSeeEvent(SampleEvent::class);
    }
}
