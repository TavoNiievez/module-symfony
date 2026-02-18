<?php

declare(strict_types=1);

namespace Tests;

use Tests\Support\KernelTestCase;

final class ServicesAssertionsTest extends KernelTestCase
{
    public function testGrabService(): void
    {
        $this->assertIsObject($this->grabService('security.helper'));
    }

    public function testPersistService(): void
    {
        $this->persistService('router');
        $this->assertArrayHasKey('router', $this->persistentServices);
    }

    public function testPersistPermanentService(): void
    {
        $this->persistPermanentService('router');
        $this->assertArrayHasKey('router', $this->permanentServices);
        $this->assertArrayHasKey('router', $this->persistentServices);
    }

    public function testUnpersistService(): void
    {
        $this->persistService('router');
        $this->unpersistService('router');
        $this->assertArrayNotHasKey('router', $this->persistentServices);
    }
}
