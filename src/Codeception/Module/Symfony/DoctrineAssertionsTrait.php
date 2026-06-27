<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Assert;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;

use function array_count_values;
use function array_filter;
use function array_keys;
use function implode;
use function interface_exists;
use function is_array;
use function is_object;
use function is_string;
use function is_subclass_of;
use function json_encode;
use function sprintf;

trait DoctrineAssertionsTrait
{
    /**
     * Asserts that no SQL query was executed more than once during the last request,
     * which usually reveals an N+1 query problem.
     * Reads the Doctrine `db` profiler collector, so DoctrineBundle and the profiler must be enabled.
     *
     * ```php
     * <?php
     * $I->dontSeeDuplicateQueries();
     * ```
     */
    public function dontSeeDuplicateQueries(): void
    {
        $statements = [];
        foreach ($this->grabDoctrineCollector(__FUNCTION__)->getQueries() as $connectionQueries) {
            if (!is_array($connectionQueries)) {
                continue;
            }
            foreach ($connectionQueries as $query) {
                if (is_array($query) && is_string($query['sql'] ?? null)) {
                    $statements[] = $query['sql'];
                }
            }
        }

        $duplicates = array_keys(array_filter(array_count_values($statements), static fn(int $count): bool => $count > 1));

        $this->assertEmpty(
            $duplicates,
            sprintf("Expected no duplicate queries, but found:\n%s", implode("\n", $duplicates))
        );
    }

    /**
     * Returns the number of rows that match the given criteria for the
     * specified Doctrine entity.
     *
     * ```php
     * <?php
     * $I->grabNumRecords(User::class, ['status' => 'active']);
     * ```
     *
     * @template T of object
     * @param class-string<T> $entityClass Fully-qualified entity class name
     * @param array<string, mixed> $criteria    Optional query criteria
     */
    public function grabNumRecords(string $entityClass, array $criteria = []): int
    {
        return $this->_getEntityManager()->getRepository($entityClass)->count($criteria);
    }

    /**
     * Obtains the Doctrine entity repository {@see EntityRepository}
     * for a given entity, repository class or interface.
     *
     * ```php
     * <?php
     * $I->grabRepository($user);                          // entity object
     * $I->grabRepository(User::class);                    // entity class
     * $I->grabRepository(UserRepository::class);          // concrete repo
     * $I->grabRepository(UserRepositoryInterface::class); // interface
     * ```
     *
     * @template T of object
     * @param object|class-string<T> $entityOrClass
     * @return ($entityOrClass is class-string<T> ? EntityRepository<T> : EntityRepository<object>)
     */
    public function grabRepository(object|string $entityOrClass): EntityRepository
    {
        $id = is_object($entityOrClass) ? $entityOrClass::class : $entityOrClass;

        if (interface_exists($id) || is_subclass_of($id, EntityRepository::class)) {
            $repo = $this->grabService($id);
            if (!($repo instanceof EntityRepository && $repo instanceof $id)) {
                Assert::fail(sprintf("'%s' is not an entity repository", $id));
            }
            /** @var EntityRepository<T>|EntityRepository<object> $repo */
            return $repo;
        }

        $em = $this->_getEntityManager();
        if ($em->getMetadataFactory()->isTransient($id)) {
            Assert::fail(sprintf("'%s' is not a managed Doctrine entity", $id));
        }

        /** @var EntityRepository<T>|EntityRepository<object> */
        return $em->getRepository($id);
    }

    /**
     * Asserts that fewer than the expected number of SQL queries were executed during the last request.
     * Useful as a ceiling to guard against N+1 regressions.
     * Reads the Doctrine `db` profiler collector, so DoctrineBundle and the profiler must be enabled.
     *
     * ```php
     * <?php
     * $I->seeNumQueriesIsLessThan(5);
     * ```
     */
    public function seeNumQueriesIsLessThan(int $expectedNumber): void
    {
        $actualNumber = $this->grabDoctrineCollector(__FUNCTION__)->getQueryCount();

        $this->assertLessThan(
            $expectedNumber,
            $actualNumber,
            sprintf('Expected less than %d queries, but %d were executed.', $expectedNumber, $actualNumber)
        );
    }

    /**
     * Asserts that a given number of records exists for the entity.
     * 'id' is the default search parameter.
     *
     * ```php
     * <?php
     * $I->seeNumRecords(1, User::class, ['name' => 'davert']);
     * $I->seeNumRecords(80, User::class);
     * ```
     *
     * @template T of object
     * @param int                  $expectedNum Expected count
     * @param class-string<T> $className   Entity class
     * @param array<string, mixed> $criteria    Optional criteria
     */
    public function seeNumRecords(int $expectedNum, string $className, array $criteria = []): void
    {
        $currentNum = $this->grabNumRecords($className, $criteria);

        $this->assertSame(
            $expectedNum,
            $currentNum,
            sprintf(
                'The number of found %s (%d) does not match expected number %d with %s',
                $className,
                $currentNum,
                $expectedNum,
                json_encode($criteria, JSON_THROW_ON_ERROR)
            )
        );
    }

    protected function grabDoctrineCollector(string $callingFunction): DoctrineDataCollector
    {
        $collector = $this->grabCollector(DataCollectorName::DB, $callingFunction);
        if (!$collector instanceof DoctrineDataCollector) {
            Assert::fail(sprintf("The 'db' collector is not a Doctrine collector, required by '%s'.", $callingFunction));
        }

        return $collector;
    }
}
