<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\DoctrineAssertionsTrait;
use PHPUnit\Framework\AssertionFailedError;
use Tests\App\Entity\User;
use Tests\App\Repository\UserRepository;
use Tests\App\Repository\UserRepositoryInterface;
use Tests\Support\CodeceptTestCase;

final class DoctrineAssertionsTest extends CodeceptTestCase
{
    use DoctrineAssertionsTrait;

    public function testDontSeeDuplicateQueries(): void
    {
        $this->requestDoctrineQueries('/doctrine-queries');

        $this->dontSeeDuplicateQueries();
    }

    public function testDontSeeDuplicateQueriesDetectsDuplicates(): void
    {
        $this->requestDoctrineQueries('/doctrine-queries?duplicate=1');

        $this->expectException(AssertionFailedError::class);
        $this->dontSeeDuplicateQueries();
    }

    public function testGrabNumRecords(): void
    {
        $this->assertSame(1, $this->grabNumRecords(User::class));
    }

    public function testGrabRepository(): void
    {
        $this->assertInstanceOf(UserRepository::class, $this->grabRepository(User::class));
        $this->assertInstanceOf(UserRepository::class, $this->grabRepository(UserRepository::class));
        $this->assertInstanceOf(UserRepository::class, $this->grabRepository($this->grabRepository(User::class)->findOneBy(['email' => 'john_doe@gmail.com'])));
        $this->assertInstanceOf(UserRepository::class, $this->grabRepository(UserRepositoryInterface::class));
    }

    public function testSeeNumQueriesIsLessThan(): void
    {
        $this->requestDoctrineQueries('/doctrine-queries');

        $this->seeNumQueriesIsLessThan(5);
    }

    public function testSeeNumRecords(): void
    {
        $this->seeNumRecords(1, User::class);
    }

    /**
     * The mini-app recreates the schema per test, which pollutes the query log.
     * Reset it before the request so the profiler only records the request's own
     * queries, as it would for a real request (the holder resets between requests).
     */
    private function requestDoctrineQueries(string $uri): void
    {
        $this->grabService('doctrine.debug_data_holder')->reset();
        $this->client->request('GET', $uri);
    }
}
