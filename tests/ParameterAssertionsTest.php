<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\CodeceptTestCase;

final class ParameterAssertionsTest extends CodeceptTestCase
{
    public function testGrabParameter(): void
    {
        $this->assertSame('Codeception', $this->grabParameter('app.business_name'));
        $this->assertSame('value', $this->grabParameter('app.param'));
    }
}
