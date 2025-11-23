<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class SecurityCest
{
    public function dontSeeAuthentication(FunctionalTester $I): void
    {
        $I->amOnPage('/dashboard');
        $I->dontSeeAuthentication();
    }

    public function dontSeeRememberedAuthentication(FunctionalTester $I): void
    {
        $user = $this->createTestUser($I, ['ROLE_USER']);
        $I->amLoggedInAs($user);

        $I->dontSeeRememberedAuthentication();
    }

    public function seeAuthentication(FunctionalTester $I): void
    {
        $user = $this->createTestUser($I, ['ROLE_USER']);
        $I->amLoggedInAs($user);

        $I->seeAuthentication();
    }

    public function seeRememberedAuthentication(FunctionalTester $I): void
    {
        $user = $this->createTestUser($I, ['ROLE_USER']);
        $I->setCookie('REMEMBERME', 'remember-token');
        $I->amLoggedInAs($user);

        $I->seeRememberedAuthentication();
    }

    public function seeUserHasRole(FunctionalTester $I): void
    {
        $user = $this->createTestUser($I, ['ROLE_USER', 'ROLE_ADMIN']);
        $I->amLoggedInAs($user);

        $I->seeUserHasRole('ROLE_ADMIN');
    }

    public function seeUserHasRoles(FunctionalTester $I): void
    {
        $user = $this->createTestUser($I, ['ROLE_USER', 'ROLE_CUSTOMER']);
        $I->amLoggedInAs($user);

        $I->seeUserHasRoles(['ROLE_USER', 'ROLE_CUSTOMER']);
    }

    public function seeUserPasswordDoesNotNeedRehash(FunctionalTester $I): void
    {
        $user = $this->createTestUser($I, ['ROLE_USER']);
        $I->amLoggedInAs($user);

        $I->seeUserPasswordDoesNotNeedRehash();
    }

    private function createTestUser(FunctionalTester $I, array $roles): \TestUser
    {
        $hasher = $I->grabService('security.password_hasher');
        $hashed = $hasher->hashPassword(new \TestUser('tmp', ''), '123456');

        return new \TestUser('john_doe@gmail.com', $hashed, $roles);
    }
}
