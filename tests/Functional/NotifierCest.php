<?php
namespace Tests\Functional;

use Symfony\Component\Notifier\Message\ChatMessage;
use Tests\FunctionalTester;
use Tests\_app\Notifier\NotifierFixture;

class NotifierCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->grabService('notifier.notification_logger_listener')->reset();
    }

    public function dontSeeNotificationIsSent(FunctionalTester $I): void
    {
        $I->dontSeeNotificationIsSent();
    }

    public function queuedAndSentNotifications(FunctionalTester $I): void
    {
        /** @var NotifierFixture $fixture */
        $fixture = $I->grabService(NotifierFixture::class);

        $sentEvent = $fixture->sendNotification('Welcome notification', 'primary');
        $queuedEvent = $fixture->sendNotification('Queued notification', 'queued', true);

        $I->assertNotificationCount(1);
        $I->assertNotificationCount(1, 'primary');
        $I->assertQueuedNotificationCount(1);
        $I->assertQueuedNotificationCount(1, 'queued');

        $I->assertNotificationIsNotQueued($sentEvent);
        $I->assertNotificationIsQueued($queuedEvent);
    }

    public function notificationSubjectAndTransportAssertions(FunctionalTester $I): void
    {
        /** @var NotifierFixture $fixture */
        $fixture = $I->grabService(NotifierFixture::class);

        $fixture->sendNotification('Primary alert', 'chat');
        $fixture->sendNotification('Secondary update', 'backup');

        $lastNotification = $I->grabLastSentNotification();
        $I->assertInstanceOf(ChatMessage::class, $lastNotification);

        $I->assertNotificationSubjectContains($lastNotification, 'update');
        $I->assertNotificationSubjectNotContains($lastNotification, 'missing');
        $I->assertNotificationTransportIsEqual($lastNotification, 'backup');
        $I->assertNotificationTransportIsNotEqual($lastNotification, 'chat');

        $notifications = $I->grabSentNotifications();
        $I->assertCount(2, $notifications);
        $I->assertSame('chat', $I->getNotifierMessage(0)?->getTransport());
    }
}
