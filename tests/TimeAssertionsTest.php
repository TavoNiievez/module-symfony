<?php

declare(strict_types=1);

namespace Tests;

use Tests\Support\CodeceptTestCase;
use Codeception\Module\Symfony\TimeAssertionsTrait;
use Codeception\Module\Symfony\HttpKernelAssertionsTrait;

final class TimeAssertionsTest extends CodeceptTestCase
{
    use HttpKernelAssertionsTrait;
    use TimeAssertionsTrait;

    public function testSeeRequestTimeIsLessThan(): void
    {
        $this->client->request('GET', '/register');
        $this->assertStringContainsString('register', $this->client->getRequest()->getPathInfo());
        $this->seeRequestTimeIsLessThan(400);
    }
}
