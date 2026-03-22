<?php

declare(strict_types=1);

namespace Tests;

use Tests\Support\CodeceptTestCase;
use Codeception\Module\Symfony\ParameterAssertionsTrait;

final class ParameterAssertionsTest extends CodeceptTestCase
{
    use ParameterAssertionsTrait;

    public function testGrabParameter(): void
    {
        $this->assertSame('Codeception', $this->grabParameter('app.business_name'));
        $this->assertSame('value', $this->grabParameter('app.param'));
    }
}
