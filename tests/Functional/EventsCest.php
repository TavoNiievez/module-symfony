<?php
namespace Tests\Functional;

use Tests\FunctionalTester;
use Tests\_app\Event\OrphanEvent;
use Tests\_app\Event\SampleEvent;
use Tests\_app\Listener\NamedEventListener;
use Tests\_app\Listener\SampleEventListener;

class EventsCest
{
    public function testEventDispatchingAndListeners(FunctionalTester $I): void
    {
        $I->amOnPage('/dispatch-event');

        $I->seeEvent(SampleEvent::class);
        $I->dontSeeEvent(OrphanEvent::class);
        $I->seeEventListenerIsCalled(SampleEventListener::class, SampleEvent::class);
        $I->dontSeeEventListenerIsCalled(NamedEventListener::class, SampleEvent::class);
        $I->dontSeeOrphanEvent();
    }

    public function testNamedEventListenerFiltering(FunctionalTester $I): void
    {
        $I->amOnPage('/dispatch-named-event');

        $I->seeEventListenerIsCalled(NamedEventListener::class, 'named.event');
        $I->dontSeeEventListenerIsCalled(SampleEventListener::class, 'named.event');
    }

    public function testOrphanEventDetection(FunctionalTester $I): void
    {
        $I->amOnPage('/dispatch-orphan-event');

        $I->seeOrphanEvent(OrphanEvent::class);
        $I->dontSeeEvent(SampleEvent::class);
    }
}
