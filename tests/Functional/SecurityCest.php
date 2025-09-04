<?php
namespace Tests\Functional;

use Symfony\Component\BrowserKit\Cookie;
use Tests\FunctionalTester;

class SecurityCest
{
    public function securityAssertions(FunctionalTester $I): void
    {
        $I->dontSeeAuthentication();
        $hasher = $I->grabService('security.password_hasher');
        $hashed = $hasher->hashPassword(new \TestUser('tmp', ''), 'password');
        $user = new \TestUser('john@example.com', $hashed, ['ROLE_USER', 'ROLE_ADMIN']);
        $I->amLoggedInAs($user);

        $I->seeAuthentication();

        $I->seeUserHasRole('ROLE_ADMIN');
        $I->seeUserHasRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $I->seeUserPasswordDoesNotNeedRehash();
    }
}
