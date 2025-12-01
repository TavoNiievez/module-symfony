<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\NotifierAssertionsTrait;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Notifier\Event\MessageEvent;
use Symfony\Component\Notifier\EventListener\NotificationLoggerListener;
use Symfony\Component\Notifier\Message\ChatMessage;
use Tests\_app\Notifier\NotifierFixture;
use Tests\Support\KernelTestCase;

final class NotifierAssertionsTest extends KernelTestCase
{
    use NotifierAssertionsTrait;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var NotificationLoggerListener $logger */
        $logger = $this->getService('notifier.notification_logger_listener');
        $logger->reset();
    }

    public function testAssertNotificationCount(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $this->assertNotificationCount(1);
        $this->assertNotificationCount(1, 'primary');
    }

    public function testAssertNotificationIsNotQueued(): void
    {
        $this->checkVersion();
        $sentEvent = $this->sendNotifications()['sent'];
        $this->assertNotificationIsNotQueued($sentEvent);
    }

    public function testAssertNotificationIsQueued(): void
    {
        $this->checkVersion();
        $queuedEvent = $this->sendNotifications()['queued'];
        $this->assertNotificationIsQueued($queuedEvent);
    }

    public function testAssertNotificationSubjectContains(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $notification = $this->getNotifierMessage();
        $this->assertNotificationSubjectContains($notification, 'Welcome');
    }

    public function testAssertNotificationSubjectNotContains(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $notification = $this->getNotifierMessage();
        $this->assertNotificationSubjectNotContains($notification, 'missing');
    }

    public function testAssertNotificationTransportIsEqual(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $this->grabLastSentNotification();

        /** @var NotifierFixture $fixture */
        $fixture = $this->getService(NotifierFixture::class);
        $fixture->sendNotification('Primary alert', 'chat');
        $last = $this->grabLastSentNotification();

        $this->assertNotificationTransportIsEqual($last, 'chat');
    }

    public function testAssertNotificationTransportIsNotEqual(): void
    {
        $this->checkVersion();
        /** @var NotifierFixture $fixture */
        $fixture = $this->getService(NotifierFixture::class);
        $fixture->sendNotification('Primary alert', 'chat');
        $last = $this->grabLastSentNotification();

        $this->assertNotificationTransportIsNotEqual($last, 'email');
    }

    public function testAssertQueuedNotificationCount(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $this->assertQueuedNotificationCount(1);
        $this->assertQueuedNotificationCount(1, 'queued');
    }

    public function testDontSeeNotificationIsSent(): void
    {
        $this->checkVersion();
        $this->dontSeeNotificationIsSent();
    }

    public function testGetNotifierEvent(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $event = $this->getNotifierEvent();
        $this->assertInstanceOf(MessageEvent::class, $event);
    }

    public function testGetNotifierEvents(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $events = $this->getNotifierEvents();
        $this->assertCount(2, $events);
    }

    public function testGetNotifierMessage(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $message = $this->getNotifierMessage();
        $this->assertInstanceOf(ChatMessage::class, $message);
    }

    public function testGetNotifierMessages(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $messages = $this->getNotifierMessages();
        $this->assertCount(2, $messages);
    }

    public function testGrabLastSentNotification(): void
    {
        $this->checkVersion();
        /** @var NotifierFixture $fixture */
        $fixture = $this->getService(NotifierFixture::class);
        $fixture->sendNotification('Last One', 'chat');

        $last = $this->grabLastSentNotification();
        $this->assertInstanceOf(ChatMessage::class, $last);
        $this->assertSame('Last One', $last->getSubject());
    }

    public function testGrabSentNotifications(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $notifications = $this->grabSentNotifications();
        $this->assertCount(2, $notifications);
    }

    public function testSeeNotificationIsSent(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $this->seeNotificationIsSent();
    }

    private function checkVersion(): void
    {
        if (Kernel::VERSION_ID < 60200) {
            $this->markTestSkipped('Notifier assertions require Symfony 6.2+');
        }
    }

    private function sendNotifications(): array
    {
        /** @var NotifierFixture $fixture */
        $fixture = $this->getService(NotifierFixture::class);

        $sentEvent = $fixture->sendNotification('Welcome notification', 'primary');
        $queuedEvent = $fixture->sendNotification('Queued notification', 'queued', true);

        return ['sent' => $sentEvent, 'queued' => $queuedEvent];
    }
}
