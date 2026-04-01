<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\DataCollectorName;
use Codeception\Module\Symfony\HttpKernelAssertionsTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;

#[AllowMockObjectsWithoutExpectations]
final class HttpKernelAssertionsTest extends TestCase
{
    private object $traitObject;

    protected function setUp(): void
    {
        $this->traitObject = new class () {
            use HttpKernelAssertionsTrait;

            public ?Profile $profile = null;

            protected function getProfile(): ?Profile
            {
                return $this->profile;
            }

            public function testGrabCollector(DataCollectorName $name, string $function = '', ?string $message = null): DataCollectorInterface
            {
                return $this->grabCollector($name, $function, $message);
            }
        };
    }

    public function testGrabCollectorThrowsErrorWhenProfileIsNull(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The Profile is needed to use the 'myFunction' function.");

        $this->traitObject->testGrabCollector(DataCollectorName::EVENTS, 'myFunction');
    }

    public function testGrabCollectorThrowsErrorWhenCollectorIsMissing(): void
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('hasCollector')->with('events')->willReturn(false);
        $this->traitObject->profile = $profile;

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'events' collector is needed to use the 'myFunction' function.");

        $this->traitObject->testGrabCollector(DataCollectorName::EVENTS, 'myFunction');
    }

    public function testGrabCollectorThrowsErrorWithCustomMessageWhenCollectorIsMissing(): void
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('hasCollector')->with('events')->willReturn(false);
        $this->traitObject->profile = $profile;

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Custom message about missing events collector.");

        $this->traitObject->testGrabCollector(DataCollectorName::EVENTS, 'myFunction', 'Custom message about missing events collector.');
    }

    public function testGrabCollectorReturnsCollector(): void
    {
        $collector = $this->createMock(DataCollectorInterface::class);

        $profile = $this->createMock(Profile::class);
        $profile->method('hasCollector')->with('events')->willReturn(true);
        $profile->method('getCollector')->with('events')->willReturn($collector);
        $this->traitObject->profile = $profile;

        $result = $this->traitObject->testGrabCollector(DataCollectorName::EVENTS, 'myFunction');

        $this->assertSame($collector, $result);
    }
}
