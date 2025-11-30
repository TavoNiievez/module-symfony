<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\MailerAssertionsTrait;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\Support\ManualKernelTestCase;

class MailerAssertionsTest extends ManualKernelTestCase
{
    use MailerAssertionsTrait;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var MessageLoggerListener $logger */
        $logger = $this->getService('mailer.message_logger_listener');
        $logger->reset();
    }

    public function testDontSeeEmailIsSentWithEmptyLogger(): void
    {
        $this->dontSeeEmailIsSent();
    }

    public function testMailerEventAssertionsAgainstSentEmail(): void
    {
        $this->client->request('GET', '/send-email');

        $this->assertEmailCount(1);
        $this->seeEmailIsSent();

        $event = $this->getMailerEvent();
        $this->assertInstanceOf(MessageEvent::class, $event);
        $this->assertEmailIsNotQueued($event);

        $email = $this->grabLastSentEmail();
        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame('jane_doe@example.com', $email->getTo()[0]->getAddress());
        $this->assertEmailCount(1, $event?->getTransport());

        $emails = $this->grabSentEmails();
        $this->assertCount(1, $emails);
    }

    public function testQueuedEmailAssertions(): void
    {
        $queuedEmail = (new Email())
            ->from('queued@example.com')
            ->to('queued@example.com');
        $envelope = new Envelope(new Address('queued@example.com'), [new Address('queued@example.com')]);
        $queuedEvent = new MessageEvent($queuedEmail, $envelope, 'smtp', true);

        /** @var MessageLoggerListener $logger */
        $logger = $this->getService('mailer.message_logger_listener');
        $logger->onMessage($queuedEvent);

        $this->assertQueuedEmailCount(1);
        $this->assertEmailIsQueued($queuedEvent);
        $this->assertEmailCount(0);
        $this->assertQueuedEmailCount(1, 'smtp', 'Queued emails can be counted by transport');
    }

    public function testTransportSpecificMailerEvents(): void
    {
        /** @var MessageLoggerListener $logger */
        $logger = $this->getService('mailer.message_logger_listener');

        $smtpEmail = (new Email())
            ->from('smtp@example.com')
            ->to('smtp@example.com');
        $smtpEnvelope = new Envelope(new Address('smtp@example.com'), [new Address('smtp@example.com')]);
        $smtpEvent = new MessageEvent($smtpEmail, $smtpEnvelope, 'smtp', false);

        $nullEmail = (new Email())
            ->from('null@example.com')
            ->to('null@example.com');
        $nullEnvelope = new Envelope(new Address('null@example.com'), [new Address('null@example.com')]);
        $nullEvent = new MessageEvent($nullEmail, $nullEnvelope, 'null', false);

        $logger->onMessage($smtpEvent);
        $logger->onMessage($nullEvent);

        $this->assertEmailCount(1, 'smtp');
        $this->assertEmailCount(1, 'null');
        $this->assertEmailCount(2);
    }
}
