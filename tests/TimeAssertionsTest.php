<?php

declare(strict_types=1);

namespace Tests;


final class TimeAssertionsTest extends \Tests\Support\KernelTestCase
{
    public function testSeeRequestTimeIsLessThan(): void
    {
        $this->client->request('GET', '/register');
        $this->assertStringContainsString('register', $this->client->getRequest()->getPathInfo());
        $this->seeRequestTimeIsLessThan(400);
    }
}
