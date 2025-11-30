<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\DataCollectorName;
use Codeception\Module\Symfony\TimeAssertionsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
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

    protected function grabCollector(DataCollectorName $name, string $function): DataCollectorInterface
    {
        /** @var Profiler $profiler */
        $profiler = self::getContainer()->get('profiler');
        $profile = $profiler->collect($this->client->getRequest(), $this->client->getResponse());
        return $profile->getCollector($name->value);
    }
}
