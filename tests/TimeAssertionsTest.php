<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\TimeAssertionsTrait;
use Tests\Support\KernelTestCase;

class TimeAssertionsTest extends KernelTestCase
{
    use TimeAssertionsTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->request('GET', '/register');
    }

    public function testRequestTime(): void
    {
        $this->assertStringContainsString('register', $this->client->getRequest()->getPathInfo());
        $this->seeRequestTimeIsLessThan(400);
    }
}
