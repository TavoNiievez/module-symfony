<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use PHPUnit\Framework\Assert;
use Symfony\Component\Messenger\DataCollector\MessengerDataCollector;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\VarDumper\Cloner\Data;

use function class_exists;
use function count;
use function is_string;
use function sprintf;

trait MessengerAssertionsTrait
{
    /**
     * Processes up to `$limit` messages queued on the given in-memory transport by handling them
     * through the bus, letting you assert the side effects (emails sent, entities persisted, ...)
     * of their handlers. Stops early once the transport's queue is empty.
     *
     * ```php
     * <?php
     * $I->consumeMessengerMessages('async');
     * $I->consumeMessengerMessages('async', limit: 3);
     * ```
     */
    public function consumeMessengerMessages(string $transportName, int $limit = 1): void
    {
        $transport = $this->grabMessengerTransport($transportName);
        $bus = $this->grabService('messenger.routable_message_bus');
        if (!$bus instanceof MessageBusInterface) {
            Assert::fail("The 'messenger.routable_message_bus' service is not a message bus.");
        }

        $processed = 0;
        while ($processed < $limit) {
            $envelopes = [...$transport->get()];
            if ($envelopes === []) {
                break;
            }

            foreach ($envelopes as $envelope) {
                $bus->dispatch($envelope->with(new ReceivedStamp($transportName), new ConsumedByWorkerStamp()));
                $transport->ack($envelope);
                if (++$processed >= $limit) {
                    break 2;
                }
            }
        }
    }

    /**
     * Asserts no message of the given class was dispatched (optionally on a single bus).
     *
     * ```php
     * <?php
     * $I->dontSeeMessageDispatched(SendWelcomeEmail::class);
     * $I->dontSeeMessageDispatched(SendWelcomeEmail::class, 'messenger.bus.default');
     * ```
     *
     * @param class-string $messageClass
     */
    public function dontSeeMessageDispatched(string $messageClass, ?string $bus = null): void
    {
        $this->assertNotContains(
            $messageClass,
            $this->getDispatchedMessageClasses(__FUNCTION__, $bus),
            sprintf("The '%s' message was dispatched%s.", $messageClass, $this->busSuffix($bus)),
        );
    }

    /**
     * Returns the dispatched message class names, in dispatch order (optionally for a single bus).
     *
     * The profiler stores cloned snapshots, so this yields class names, not the message objects.
     *
     * ```php
     * <?php
     * $classes = $I->grabDispatchedMessageClasses();
     * $classes = $I->grabDispatchedMessageClasses('messenger.bus.default');
     * ```
     *
     * @return list<class-string>
     */
    public function grabDispatchedMessageClasses(?string $bus = null): array
    {
        return $this->getDispatchedMessageClasses(__FUNCTION__, $bus);
    }

    /**
     * Grabs the in-memory transport with the given name, so you can inspect the real message objects
     * it received via `getSent()`, `getAcknowledged()`, `getRejected()` or `get()`.
     * The app must route the bus to an in-memory transport in the test environment
     * (`MESSENGER_TRANSPORT_DSN=in-memory://`).
     *
     * ```php
     * <?php
     * $envelope = $I->grabMessengerTransport('async')->getSent()[0];
     * ```
     */
    public function grabMessengerTransport(string $transportName): InMemoryTransport
    {
        $transport = $this->grabService('messenger.transport.' . $transportName);
        if (!$transport instanceof InMemoryTransport) {
            Assert::fail(sprintf("The '%s' transport is not an in-memory transport.", $transportName));
        }

        return $transport;
    }

    /**
     * Asserts how many messages were dispatched (optionally on a single bus).
     *
     * ```php
     * <?php
     * $I->seeDispatchedMessageCount(1);
     * $I->seeDispatchedMessageCount(2, 'messenger.bus.default');
     * ```
     */
    public function seeDispatchedMessageCount(int $expectedCount, ?string $bus = null): void
    {
        $messages = $this->grabMessengerCollector(__FUNCTION__)->getMessages($bus);

        $this->assertCount(
            $expectedCount,
            $messages,
            sprintf(
                'Expected %d message(s) to be dispatched%s, but %d were.',
                $expectedCount,
                $this->busSuffix($bus),
                count($messages),
            ),
        );
    }

    /**
     * Asserts at least one message of the given class was dispatched (optionally on a single bus).
     *
     * ```php
     * <?php
     * $I->seeMessageDispatched(SendWelcomeEmail::class);
     * $I->seeMessageDispatched(SendWelcomeEmail::class, 'messenger.bus.default');
     * ```
     *
     * @param class-string $messageClass
     */
    public function seeMessageDispatched(string $messageClass, ?string $bus = null): void
    {
        $this->assertContains(
            $messageClass,
            $this->getDispatchedMessageClasses(__FUNCTION__, $bus),
            sprintf("The '%s' message was not dispatched%s.", $messageClass, $this->busSuffix($bus)),
        );
    }

    /**
     * Asserts how many messages were sent to the given in-memory transport.
     *
     * ```php
     * <?php
     * $I->seeMessengerQueueCount(1, 'async');
     * ```
     */
    public function seeMessengerQueueCount(int $expectedCount, string $transportName): void
    {
        $sent = $this->grabMessengerTransport($transportName)->getSent();

        $this->assertCount(
            $expectedCount,
            $sent,
            sprintf("Expected %d message(s) on the '%s' transport, but %d were sent.", $expectedCount, $transportName, count($sent)),
        );
    }

    /**
     * Asserts that a message of the given class was sent to the given in-memory transport.
     *
     * ```php
     * <?php
     * $I->seeMessengerTransportContains(SendInvoice::class, 'async');
     * ```
     *
     * @param class-string $messageClass
     */
    public function seeMessengerTransportContains(string $messageClass, string $transportName): void
    {
        $classes = [];
        foreach ($this->grabMessengerTransport($transportName)->getSent() as $envelope) {
            $classes[] = $envelope->getMessage()::class;
        }

        $this->assertContains(
            $messageClass,
            $classes,
            sprintf("No '%s' message was sent to the '%s' transport.", $messageClass, $transportName),
        );
    }

    /**
     * @return list<class-string>
     */
    private function getDispatchedMessageClasses(string $callingFunction, ?string $bus): array
    {
        $classes = [];
        foreach ($this->grabMessengerCollector($callingFunction)->getMessages($bus) as $entry) {
            if (!$entry instanceof Data) {
                continue;
            }

            $message = $entry['message'];
            $type = $message instanceof Data ? ($message['type'] ?? null) : null;
            if ($type instanceof Data) {
                $type = $type->getValue();
            }

            if (is_string($type) && class_exists($type)) {
                $classes[] = $type;
            }
        }

        return $classes;
    }

    private function busSuffix(?string $bus): string
    {
        return $bus !== null ? sprintf(" on bus '%s'", $bus) : '';
    }

    protected function grabMessengerCollector(string $callingFunction): MessengerDataCollector
    {
        return $this->grabCollector(DataCollectorName::MESSENGER, $callingFunction);
    }
}
