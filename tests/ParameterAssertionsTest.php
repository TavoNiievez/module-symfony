<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\ParameterAssertionsTrait;
use Tests\Support\KernelTestCase;

class ParameterAssertionsTest extends KernelTestCase
{
    use ParameterAssertionsTrait;

    public function testGrabParameter(): void
    {
        $this->assertSame('value', $this->grabParameter('app.param'));
    }

    public function testGrabBusinessNameParameter(): void
    {
        $this->assertSame('Codeception', $this->grabParameter('app.business_name'));
    }
}
