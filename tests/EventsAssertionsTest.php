<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\EventsAssertionsTrait;
use Codeception\Module\Symfony\DataCollectorName;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Tests\_app\Event\NamedEvent;
use Tests\_app\Event\OrphanEvent;
use Tests\_app\Event\SampleEvent;
use Tests\_app\Listener\NamedEventListener;
use Tests\_app\Listener\SampleEventListener;
use Tests\Support\KernelTestCase;

class EventsAssertionsTest extends KernelTestCase
{
    use EventsAssertionsTrait;

    protected array $kernelOptions = ['debug' => true];
    protected bool $profilerEnabled = true;

    protected function grabCollector(DataCollectorName $name, string $function): DataCollectorInterface
    {
        return $this->getProfile()->getCollector($name->value);
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

    public function testEventDispatchingAndListeners(): void
    {
        $this->client->request('GET', '/dispatch-event');

        $this->seeEvent(SampleEvent::class);
        $this->dontSeeEvent(OrphanEvent::class);
        $this->seeEventListenerIsCalled(SampleEventListener::class, SampleEvent::class);
        $this->dontSeeEventListenerIsCalled(NamedEventListener::class, SampleEvent::class);

        if (\Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 60000) {
            $this->dontSeeOrphanEvent();
        }
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
