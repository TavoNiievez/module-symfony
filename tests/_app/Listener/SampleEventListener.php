<?php

namespace Tests\_app\Listener;

use Tests\_app\Event\SampleEvent;

class SampleEventListener
{
    public function __invoke(SampleEvent $event): void
    {
    }
}
