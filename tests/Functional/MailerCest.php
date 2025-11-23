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
    public function _before(FunctionalTester $I): void
    {
        $logger = $I->grabService('mailer.message_logger_listener');
        $logger->reset();
    }

    public function dontSeeEmailIsSent(FunctionalTester $I): void
    {
        $I->dontSeeEmailIsSent();
    }

    public function queuedEmailAssertions(FunctionalTester $I): void
    {
        /** @var MessageLoggerListener $logger */
        $logger = $I->grabService('mailer.message_logger_listener');

        $queuedEmail = (new Email())->from('queued@example.com')->to('queued@example.com');
        $queuedEnvelope = new Envelope(new Address('queued@example.com'), [new Address('queued@example.com')]);
        $queuedEvent = new MessageEvent($queuedEmail, $queuedEnvelope, 'smtp', true);
        $logger->onMessage($queuedEvent);

        $I->assertQueuedEmailCount(1);
        $I->assertEmailIsQueued($queuedEvent);
        $I->assertEmailCount(0);
        $I->assertQueuedEmailCount(1, 'smtp');
    }

    public function mailerEventAssertions(FunctionalTester $I): void
    {
        $I->amOnRoute('send_email');

        $I->assertEmailCount(1);
        $I->seeEmailIsSent();

        $event = $I->getMailerEvent();
        $I->assertEmailIsNotQueued($event);

        $email = $I->grabLastSentEmail();
        $I->assertSame('jane_doe@example.com', $email->getTo()[0]->getAddress());

        $emails = $I->grabSentEmails();
        $I->assertCount(1, $emails);
    }

    public function transportSpecificMailerEvents(FunctionalTester $I): void
    {
        /** @var MessageLoggerListener $logger */
        $logger = $I->grabService('mailer.message_logger_listener');

        $smtpEmail = (new Email())->from('smtp@example.com')->to('smtp@example.com');
        $smtpEnvelope = new Envelope(new Address('smtp@example.com'), [new Address('smtp@example.com')]);
        $smtpEvent = new MessageEvent($smtpEmail, $smtpEnvelope, 'smtp', false);

        $nullEmail = (new Email())->from('null@example.com')->to('null@example.com');
        $nullEnvelope = new Envelope(new Address('null@example.com'), [new Address('null@example.com')]);
        $nullEvent = new MessageEvent($nullEmail, $nullEnvelope, 'null', false);

        $logger->onMessage($smtpEvent);
        $logger->onMessage($nullEvent);

        $I->assertEmailCount(1, 'smtp');
        $I->assertEmailCount(1, 'null');
        $I->assertEmailCount(2);
    }
}
