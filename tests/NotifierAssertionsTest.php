<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\NotifierAssertionsTrait;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Notifier\Event\MessageEvent;
use Symfony\Component\Notifier\EventListener\NotificationLoggerListener;
use Symfony\Component\Notifier\Message\ChatMessage;
use Tests\_app\Notifier\NotifierFixture;
use Tests\Support\ManualKernelTestCase;

class NotifierAssertionsTest extends ManualKernelTestCase
{
    use NotifierAssertionsTrait;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var NotificationLoggerListener $logger */
        $logger = $this->getService('notifier.notification_logger_listener');
        $logger->reset();
    }

    public function testNoNotificationsSent(): void
    {
        if (Kernel::VERSION_ID < 60200) {
            $this->expectThrowable(AssertionFailedError::class, function () {
                $this->dontSeeNotificationIsSent();
            });
            return;
        }

        $this->dontSeeNotificationIsSent();
    }

    public function testQueuedAndSentNotifications(): void
    {
        if (Kernel::VERSION_ID < 60200) {
            $this->expectThrowable(AssertionFailedError::class, function () {
                $this->assertNotificationCount(1);
            });
            return;
        }

        /** @var NotifierFixture $fixture */
        $fixture = $this->getService(NotifierFixture::class);

        $sentEvent = $fixture->sendNotification('Welcome notification', 'primary');
        $queuedEvent = $fixture->sendNotification('Queued notification', 'queued', true);

        $this->assertNotificationCount(1);
        $this->assertNotificationCount(1, 'primary');
        $this->assertQueuedNotificationCount(1);
        $this->assertQueuedNotificationCount(1, 'queued');

        $this->assertNotificationIsNotQueued($sentEvent);
        $this->assertNotificationIsQueued($queuedEvent);

        $firstEvent = $this->getNotifierEvent();
        $this->assertInstanceOf(MessageEvent::class, $firstEvent);
        $this->assertNotificationIsNotQueued($firstEvent);
    }

    public function testNotificationSubjectAndTransportAssertions(): void
    {
        if (Kernel::VERSION_ID < 60200) {
            $this->expectThrowable(\Error::class, function () {
                $this->assertNotificationSubjectContains(new ChatMessage('test'), 'update');
            });
            return;
        }

        /** @var NotifierFixture $fixture */
        $fixture = $this->getService(NotifierFixture::class);

        $fixture->sendNotification('Primary alert', 'chat');
        $fixture->sendNotification('Secondary update', 'backup');

        $lastNotification = $this->grabLastSentNotification();
        $this->assertInstanceOf(ChatMessage::class, $lastNotification);

        $this->assertNotificationSubjectContains($lastNotification, 'update');
        $this->assertNotificationSubjectNotContains($lastNotification, 'missing');
        $this->assertNotificationTransportIsEqual($lastNotification, 'backup');
        $this->assertNotificationTransportIsNotEqual($lastNotification, 'chat');

        $notifications = $this->grabSentNotifications();
        $this->assertCount(2, $notifications);
        $this->assertSame('chat', $this->getNotifierMessage(0)?->getTransport());
    }

    protected function expectThrowable(string $exception, callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            if ($e instanceof $exception) {
                $this->assertTrue(true);
                return;
            }
            throw $e;
        }
        $this->fail("Expected exception $exception was not thrown");
    }
}
