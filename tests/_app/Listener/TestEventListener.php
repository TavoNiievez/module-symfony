<?php

declare(strict_types=1);

namespace Tests\_app\Listener;

use Tests\_app\Event\TestEvent;

class TestEventListener
{
    public function onTestEvent(TestEvent $event): void {}

    public function onNamedEvent(TestEvent $event): void {}
}
