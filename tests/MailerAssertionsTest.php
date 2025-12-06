<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\CodeceptTestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class MailerAssertionsTest extends CodeceptTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $logger = $this->getService('mailer.message_logger_listener');
        $this->assertInstanceOf(MessageLoggerListener::class, $logger);
        $logger->reset();
    }

    public function testAssertEmailCount(): void
    {
        $this->client->request('GET', '/send-email');
        $this->assertEmailCount(1);
    }

    public function testAssertEmailIsNotQueued(): void
    {
        $this->client->request('GET', '/send-email');
        $event = $this->getMailerEvent();
        $this->assertEmailIsNotQueued($event);
    }

    public function testAssertEmailIsQueued(): void
    {
        $queuedEvent = $this->createQueuedEvent();
        $this->getService('mailer.message_logger_listener')->onMessage($queuedEvent);

        $this->assertEmailIsQueued($queuedEvent);
    }

    public function testAssertQueuedEmailCount(): void
    {
        $queuedEvent = $this->createQueuedEvent();
        $this->getService('mailer.message_logger_listener')->onMessage($queuedEvent);

        $this->assertQueuedEmailCount(1);
        $this->assertQueuedEmailCount(1, 'smtp');
    }

    public function testDontSeeEmailIsSent(): void
    {
        $this->dontSeeEmailIsSent();
    }

    public function testGetMailerEvent(): void
    {
        $this->client->request('GET', '/send-email');
        $event = $this->getMailerEvent();
        $this->assertInstanceOf(MessageEvent::class, $event);
    }

    public function testGrabLastSentEmail(): void
    {
        $this->client->request('GET', '/send-email');
        $email = $this->grabLastSentEmail();
        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame('jane_doe@example.com', $email->getTo()[0]->getAddress());
    }

    public function testGrabSentEmails(): void
    {
        $this->client->request('GET', '/send-email');
        $emails = $this->grabSentEmails();
        $this->assertCount(1, $emails);
    }

    public function testSeeEmailIsSent(): void
    {
        $this->client->request('GET', '/send-email');
        $this->seeEmailIsSent();
    }

    private function createQueuedEvent(): MessageEvent
    {
        $queuedEmail = (new Email())
            ->from('queued@example.com')
            ->to('queued@example.com');
        $envelope = new Envelope(new Address('queued@example.com'), [new Address('queued@example.com')]);
        return new MessageEvent($queuedEmail, $envelope, 'smtp', true);
    }
}
