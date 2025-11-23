<?php
namespace Tests\Functional;

use Tests\FunctionalTester;
use Symfony\Bundle\SecurityBundle\Security;

class ServicesCest
{
    public function grabService(FunctionalTester $I): void
    {
        $securityHelper = $I->grabService('security.helper');

        $I->assertInstanceOf(Security::class, $securityHelper);
    }

    public function servicesPersistence(FunctionalTester $I): void
    {
        $I->persistService('router');
        $I->persistPermanentService('router');
        $I->unpersistService('router');
    }
}
