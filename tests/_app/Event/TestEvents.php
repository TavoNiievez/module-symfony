<?php

namespace Tests\_app\Event;

use Symfony\Contracts\EventDispatcher\Event;

class SampleEvent extends Event
{
}

class NamedEvent extends Event
{
}

class OrphanEvent extends Event
{
}
