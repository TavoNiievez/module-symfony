<?php

namespace Tests;

use Codeception\Module\Symfony\MailerAssertionsTrait;
use Codeception\Module\Symfony\MimeAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Mime\Email;

class MimeAssertionsTest extends TestCase
{
    use MailerAssertionsTrait;
    use MimeAssertionsTrait;

    private KernelBrowser $client;
    private \TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new \TestKernel('test', true);
        $this->kernel->boot();
        $this->client = new KernelBrowser($this->kernel);
        $this->getService('mailer.message_logger_listener')->reset();

        $mailer = $this->getService('mailer');
        $mailer->send((new Email())
            ->from('john_doe@example.com')
            ->to('jane_doe@example.com')
            ->subject('Test')
            ->text('Example text body')
            ->html('<p>HTML body</p>')
            ->attach('Attachment content', 'test.txt')
        );
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

    public function testMimeAssertions(): void
    {
        $this->assertEmailAddressContains('To', 'jane_doe@example.com');
        $this->assertEmailAttachmentCount(1);
        $this->assertEmailHasHeader('To');
        $this->assertEmailHeaderSame('To', 'jane_doe@example.com');
        $this->assertEmailHeaderNotSame('To', 'john_doe@example.com');
        $this->assertEmailHtmlBodyContains('HTML body');
        $this->assertEmailHtmlBodyNotContains('password');
        $this->assertEmailNotHasHeader('Bcc');
        $this->assertEmailTextBodyContains('Example text body');
        $this->assertEmailTextBodyNotContains('Secret');
    }
}
