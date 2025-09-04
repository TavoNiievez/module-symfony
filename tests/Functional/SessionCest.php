<?php
namespace Tests\Functional;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Tests\FunctionalTester;

class SessionCest
{
    public function sessionAssertions(FunctionalTester $I): void
    {
        $container = $I->grabService('service_container');
        $factory = $I->grabService('session.factory');
        $session = $factory->createSession();
        $container->set('session', $session);
        $session->set('key1', 'value1');
        $session->set('key2', 'value2');
        $session->save();

        $I->seeInSession('key1');
        $I->seeInSession('key1', 'value1');
        $I->dontSeeInSession('missing');
        $I->dontSeeInSession('key1', 'other');
        $I->seeSessionHasValues(['key1', 'key2']);
        $I->seeSessionHasValues(['key1' => 'value1', 'key2' => 'value2']);
    }

    public function loginAndLogoutAssertions(FunctionalTester $I): void
    {
        $user = new InMemoryUser('john@example.com', null, ['ROLE_USER']);

        $I->amLoggedInAs($user);
        $I->seeInSession('_security_main');
        $I->logout();
        $I->dontSeeInSession('_security_main');

        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);
        $I->amLoggedInWithToken($token);
        $I->seeInSession('_security_main');
        $I->goToLogoutPath();

        $I->amLoggedInAs($user);
        $I->seeInSession('_security_main');
        $I->logoutProgrammatically();
        $I->dontSeeInSession('_security_main');
    }
}
