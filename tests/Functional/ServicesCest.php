<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class ServicesCest
{
    public function servicesAssertions(FunctionalTester $I): void
    {
        $I->grabService('router');
        $I->persistService('router');
        $I->persistPermanentService('router');
        $I->unpersistService('router');
    }
}
