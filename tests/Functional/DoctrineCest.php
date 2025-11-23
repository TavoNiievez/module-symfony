<?php
namespace Tests\Functional;

use Tests\FunctionalTester;
use Tests\_app\Entity\User;
use Tests\_app\Repository\Model\UserRepositoryInterface;
use Tests\_app\Repository\UserRepository;

final class DoctrineCest
{
    public function grabNumRecords(FunctionalTester $I): void
    {
        $I->assertSame(1, $I->grabNumRecords(User::class));
    }

    public function grabRepository(FunctionalTester $I): void
    {
        $repository = $I->grabRepository(User::class);
        $I->assertInstanceOf(UserRepository::class, $repository);

        $repositoryFromClass = $I->grabRepository(UserRepository::class);
        $I->assertInstanceOf(UserRepository::class, $repositoryFromClass);

        $user = $repository->findOneBy(['email' => 'john_doe@gmail.com']);
        $I->assertNotNull($user);

        $repositoryFromEntity = $I->grabRepository($user);
        $I->assertInstanceOf(UserRepository::class, $repositoryFromEntity);

        $repositoryFromInterface = $I->grabRepository(UserRepositoryInterface::class);
        $I->assertInstanceOf(UserRepository::class, $repositoryFromInterface);
    }

    public function seeNumRecords(FunctionalTester $I): void
    {
        $I->seeNumRecords(1, User::class);
    }
}
