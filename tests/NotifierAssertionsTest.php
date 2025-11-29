<?php

namespace Tests;

use Codeception\Module\Symfony\NotifierAssertionsTrait;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Notifier\Event\MessageEvent;
use Symfony\Component\Notifier\Message\ChatMessage;
use Tests\_app\Notifier\NotifierFixture;

class NotifierAssertionsTest extends TestCase
{
    use NotifierAssertionsTrait;

    private KernelBrowser $client;

    private \TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new \TestKernel('test', true);
        $this->kernel->boot();
        $this->client = new KernelBrowser($this->kernel);
        $this->getService('notifier.notification_logger_listener')->reset();
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

    public function testNoNotificationsSent(): void
    {
        if (Kernel::VERSION_ID < 60200) {
            $this->expectThrowable(AssertionFailedError::class, function () {
                $this->dontSeeNotificationIsSent();
            });
            return;
        }

        $this->dontSeeNotificationIsSent();
    }

    public function testQueuedAndSentNotifications(): void
    {
        if (Kernel::VERSION_ID < 60200) {
            $this->expectThrowable(AssertionFailedError::class, function () {
                $this->assertNotificationCount(1);
            });
            return;
        }

        /** @var NotifierFixture $fixture */
        $fixture = $this->getService(NotifierFixture::class);

        $sentEvent = $fixture->sendNotification('Welcome notification', 'primary');
        $queuedEvent = $fixture->sendNotification('Queued notification', 'queued', true);

        $this->assertNotificationCount(1);
        $this->assertNotificationCount(1, 'primary');
        $this->assertQueuedNotificationCount(1);
        $this->assertQueuedNotificationCount(1, 'queued');

        $this->assertNotificationIsNotQueued($sentEvent);
        $this->assertNotificationIsQueued($queuedEvent);

        $firstEvent = $this->getNotifierEvent();
        $this->assertInstanceOf(MessageEvent::class, $firstEvent);
        $this->assertNotificationIsNotQueued($firstEvent);
    }

    public function testNotificationSubjectAndTransportAssertions(): void
    {
        if (Kernel::VERSION_ID < 60200) {
            $this->expectThrowable(\Error::class, function () {
                $this->assertNotificationSubjectContains(new ChatMessage('test'), 'update');
            });
            return;
        }

        /** @var NotifierFixture $fixture */
        $fixture = $this->getService(NotifierFixture::class);

        $fixture->sendNotification('Primary alert', 'chat');
        $fixture->sendNotification('Secondary update', 'backup');

        $lastNotification = $this->grabLastSentNotification();
        $this->assertInstanceOf(ChatMessage::class, $lastNotification);

        $this->assertNotificationSubjectContains($lastNotification, 'update');
        $this->assertNotificationSubjectNotContains($lastNotification, 'missing');
        $this->assertNotificationTransportIsEqual($lastNotification, 'backup');
        $this->assertNotificationTransportIsNotEqual($lastNotification, 'chat');

        $notifications = $this->grabSentNotifications();
        $this->assertCount(2, $notifications);
        $this->assertSame('chat', $this->getNotifierMessage(0)?->getTransport());
    }

    protected function expectThrowable(string $exception, callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            if ($e instanceof $exception) {
                $this->assertTrue(true);
                return;
            }
            throw $e;
        }
        $this->fail("Expected exception $exception was not thrown");
    }
}
