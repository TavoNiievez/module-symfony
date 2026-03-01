<?php

declare(strict_types=1);

namespace Tests;

use Tests\Support\KernelTestCase;

final class ParameterAssertionsTest extends KernelTestCase
{
    public function testGrabParameter(): void
    {
        $this->assertSame('Codeception', $this->grabParameter('app.business_name'));
        $this->assertSame('value', $this->grabParameter('app.param'));
    }
}
