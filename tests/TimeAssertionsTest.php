<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\CodeceptTestCase;

final class TimeAssertionsTest extends CodeceptTestCase
{
    public function testSeeRequestTimeIsLessThan(): void
    {
        $this->client->request('GET', '/register');
        $this->assertStringContainsString('register', $this->client->getRequest()->getPathInfo());
        $this->seeRequestTimeIsLessThan(400);
    }
}
