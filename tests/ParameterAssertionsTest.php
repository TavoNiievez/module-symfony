<?php

declare(strict_types=1);

namespace Tests;


final class ParameterAssertionsTest extends \Tests\Support\KernelTestCase
{
    public function testGrabParameter(): void
    {
        $this->assertSame('Codeception', $this->grabParameter('app.business_name'));
        $this->assertSame('value', $this->grabParameter('app.param'));
    }
}
