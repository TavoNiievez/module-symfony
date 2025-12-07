<?php

declare(strict_types=1);

namespace Tests;

use Doctrine\ORM\EntityManagerInterface;
use Tests\App\Entity\User;
use Tests\App\Repository\UserRepository;
use Tests\App\Repository\UserRepositoryInterface;

final class DoctrineAssertionsTest extends \Tests\Support\KernelTestCase
{
    protected function _getEntityManager(): EntityManagerInterface
    {
        return $this->_getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testGrabNumRecords(): void
    {
        $this->assertSame(1, $this->grabNumRecords(User::class));
    }

    public function testGrabRepository(): void
    {
        $repository = $this->grabRepository(User::class);
        $this->assertInstanceOf(UserRepository::class, $repository);

        $repositoryFromId = $this->grabRepository(UserRepository::class);
        $this->assertInstanceOf(UserRepository::class, $repositoryFromId);

        $user = $repository->findOneBy(['email' => 'john_doe@gmail.com']);
        $this->assertNotNull($user);

        $repositoryFromEntity = $this->grabRepository($user);
        $this->assertInstanceOf(UserRepository::class, $repositoryFromEntity);

        $repositoryFromInterface = $this->grabRepository(UserRepositoryInterface::class);
        $this->assertInstanceOf(UserRepository::class, $repositoryFromInterface);
    }

    public function testSeeNumRecords(): void
    {
        $this->seeNumRecords(1, User::class);
    }
}
