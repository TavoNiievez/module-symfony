<?php

namespace Tests;

use Codeception\Module\Symfony\MailerAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailerAssertionsTest extends TestCase
{
    use MailerAssertionsTrait;

    private KernelBrowser $client;

    private \TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new \TestKernel('test', true);
        $this->kernel->boot();
        $this->client = new KernelBrowser($this->kernel);
        $this->getService('mailer.message_logger_listener')->reset();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
        restore_exception_handler();
        parent::tearDown();
    }

    protected function getClient(): KernelBrowser
    {
        return $this->client;
    }

    protected function getService(string $serviceId): object
    {
        $container = $this->kernel->getContainer();
        if ($container->has('test.service_container')) {
            $container = $container->get('test.service_container');
        }
        return $container->get($serviceId);
    }

    public function testMailerAssertions(): void
    {
        $this->dontSeeEmailIsSent();

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

        $mailer = $this->getService('mailer');
        $mailer->send((new Email())
            ->from('john_doe@example.com')
            ->to('jane_doe@example.com')
            ->subject('Test')
            ->text('Example text body')
            ->html('<p>HTML body</p>')
            ->attach('Attachment content', 'test.txt')
        );

        $this->assertEmailCount(1);
        $this->seeEmailIsSent();
        $this->grabLastSentEmail();
        $this->grabSentEmails();
        $event = $this->getMailerEvent(1);
        $this->assertEmailIsNotQueued($event);
    }
}
