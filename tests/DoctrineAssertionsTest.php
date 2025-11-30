<?php

declare(strict_types=1);

namespace Tests;

use Codeception\Module\Symfony\DoctrineAssertionsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tests\_app\Entity\User;
use Tests\_app\Repository\UserRepositoryInterface;
use Tests\_app\Repository\UserRepository;
use Tests\Support\KernelTestCase;

class DoctrineAssertionsTest extends KernelTestCase
{
    use DoctrineAssertionsTrait;

    protected function _getContainer(): ContainerInterface
    {
        return self::getContainer();
    }

    protected function _getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine.orm.entity_manager');
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
