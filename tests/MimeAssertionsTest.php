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
    private \Tests\_app\TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new \Tests\_app\TestKernel('test', true);
        $this->kernel->boot();
        $this->client = new KernelBrowser($this->kernel);
        $this->getService('mailer.message_logger_listener')->reset();

        $this->client->request('GET', '/send-email');
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

    public function testAssertEmailAddressContains(): void
    {
        $this->assertEmailAddressContains('To', 'jane_doe@example.com');
    }

    public function testAssertEmailAttachmentCount(): void
    {
        $this->assertEmailAttachmentCount(1);
    }

    public function testAssertEmailHasHeader(): void
    {
        $this->assertEmailHasHeader('To');
    }

    public function testAssertEmailHeaderSame(): void
    {
        $this->assertEmailHeaderSame('To', 'jane_doe@example.com');
    }

    public function testAssertEmailHeaderNotSame(): void
    {
        $this->assertEmailHeaderNotSame('To', 'john_doe@example.com');
    }

    public function testAssertEmailHtmlBodyContains(): void
    {
        $this->assertEmailHtmlBodyContains('Example Email');
    }

    public function testAssertEmailHtmlBodyNotContains(): void
    {
        $this->assertEmailHtmlBodyNotContains('userpassword');
    }

    public function testAssertEmailNotHasHeader(): void
    {
        $this->assertEmailNotHasHeader('Bcc');
    }

    public function testAssertEmailTextBodyContains(): void
    {
        $this->assertEmailTextBodyContains('Example text body');
    }

    public function testAssertEmailTextBodyNotContains(): void
    {
        $this->assertEmailTextBodyNotContains('My secret text body');
    }

    public function testAssertionsWorkWithProvidedEmail(): void
    {
        $email = (new Email())
            ->from('custom@example.com')
            ->to('custom@example.com')
            ->text('Custom body text');

        $this->assertEmailAddressContains('To', 'custom@example.com', $email);
        $this->assertEmailTextBodyContains('Custom body text', $email);
        $this->assertEmailNotHasHeader('Cc', $email);
    }
}
