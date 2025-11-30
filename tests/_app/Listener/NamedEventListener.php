<?php

declare(strict_types=1);

namespace Tests\_app\Listener;

use Tests\_app\Event\NamedEvent;

class NamedEventListener
{
    public function onNamedEvent(NamedEvent $event): void {}
}
