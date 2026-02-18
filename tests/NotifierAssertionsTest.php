<?php

declare(strict_types=1);

namespace Tests;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Notifier\Event\MessageEvent;
use Symfony\Component\Notifier\Message\ChatMessage;
use Tests\App\Notifier\NotifierFixture;
use Tests\Support\KernelTestCase;

final class NotifierAssertionsTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->grabService('notifier.notification_logger_listener')->reset();
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
        $this->assertNotificationIsNotQueued($this->sendNotifications()['sent']);
    }

    public function testAssertNotificationIsQueued(): void
    {
        $this->checkVersion();
        $this->assertNotificationIsQueued($this->sendNotifications()['queued']);
    }

    public function testAssertNotificationSubjectContains(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $this->assertNotificationSubjectContains($this->getNotifierMessage(), 'Welcome');
    }

    public function testAssertNotificationSubjectNotContains(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $this->assertNotificationSubjectNotContains($this->getNotifierMessage(), 'missing');
    }

    public function testAssertNotificationTransportIsEqual(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $this->grabLastSentNotification();
        $this->grabService(NotifierFixture::class)->sendNotification('Primary alert', 'chat');
        $this->assertNotificationTransportIsEqual($this->grabLastSentNotification(), 'chat');
    }

    public function testAssertNotificationTransportIsNotEqual(): void
    {
        $this->checkVersion();
        $this->grabService(NotifierFixture::class)->sendNotification('Primary alert', 'chat');
        $this->assertNotificationTransportIsNotEqual($this->grabLastSentNotification(), 'email');
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
        $this->assertInstanceOf(MessageEvent::class, $this->getNotifierEvent());
    }

    public function testGetNotifierEvents(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $this->assertCount(2, $this->getNotifierEvents());
    }

    public function testGetNotifierMessage(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $this->assertInstanceOf(ChatMessage::class, $this->getNotifierMessage());
    }

    public function testGetNotifierMessages(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $this->assertCount(2, $this->getNotifierMessages());
    }

    public function testGrabLastSentNotification(): void
    {
        $this->checkVersion();
        $this->grabService(NotifierFixture::class)->sendNotification('Last One', 'chat');
        $last = $this->grabLastSentNotification();
        $this->assertInstanceOf(ChatMessage::class, $last);
        $this->assertSame('Last One', $last->getSubject());
    }

    public function testGrabSentNotifications(): void
    {
        $this->checkVersion();
        $this->sendNotifications();
        $this->assertCount(2, $this->grabSentNotifications());
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
        $fixture = $this->grabService(NotifierFixture::class);
        return [
            'sent' => $fixture->sendNotification('Welcome notification', 'primary'),
            'queued' => $fixture->sendNotification('Queued notification', 'queued', true)
        ];
    }
}
