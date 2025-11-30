<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\EventsAssertionsTrait;
use Codeception\Module\Symfony\DataCollectorName;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Tests\_app\Event\TestEvent;
use Tests\_app\Listener\TestEventListener;
use Tests\Support\KernelTestCase;

class EventsAssertionsTest extends KernelTestCase
{
    use EventsAssertionsTrait;

    protected array $kernelOptions = ['debug' => true];
    protected bool $profilerEnabled = true;

    public function testEventDispatchingAndListeners(): void
    {
        $this->client->request('GET', '/dispatch-event');

        $this->seeEvent(TestEvent::class);
        $this->dontSeeEvent('orphan.event');
        $this->seeEventListenerIsCalled(TestEventListener::class, TestEvent::class);

        if (\Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 60000) {
            $this->dontSeeOrphanEvent();
        }
    }

    public function testNamedEventListenerFiltering(): void
    {
        $this->client->request('GET', '/dispatch-named-event');

        $this->seeEventListenerIsCalled(TestEventListener::class, 'named.event');
    }

    public function testOrphanEventDetection(): void
    {
        $this->client->request('GET', '/dispatch-orphan-event');

        $this->seeOrphanEvent('orphan.event');
        $this->dontSeeEvent(TestEvent::class);
    }
}
