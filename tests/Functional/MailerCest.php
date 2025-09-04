<?php
namespace Tests\Functional;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\FunctionalTester;

class MailerCest
{
    public function mailerAssertions(FunctionalTester $I): void
    {
        /** @var MessageLoggerListener $logger */
        $logger = $I->grabService('mailer.message_logger_listener');
        $logger->reset();
        $I->dontSeeEmailIsSent();

        $queuedEmail = (new Email())->from('queued@example.com')->to('queued@example.com');
        $envelope = new Envelope(new Address('queued@example.com'), [new Address('queued@example.com')]);
        $queuedEvent = new MessageEvent($queuedEmail, $envelope, 'smtp', true);
        $logger->onMessage($queuedEvent);

        $I->assertQueuedEmailCount(1);
        $I->assertEmailIsQueued($queuedEvent);

        $I->amOnRoute('send_email');

        $I->assertEmailCount(1);
        $I->seeEmailIsSent();
        $I->grabLastSentEmail();
        $I->grabSentEmails();
        $event = $I->getMailerEvent(1);
        $I->assertEmailIsNotQueued($event);
    }
}
