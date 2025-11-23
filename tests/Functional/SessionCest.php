<?php
namespace Tests\Functional;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Tests\_app\Repository\UserRepository;
use Tests\FunctionalTester;

class SessionCest
{
    public function sessionAssertions(FunctionalTester $I): void
    {
        $container = $I->grabService('service_container');
        $factory = $I->grabService('session.factory');
        $session = $factory->createSession();
        $session->start();
        $container->set('session', $session);
        $I->persistService('session');

        $I->amOnRoute('session');
        $I->seeInSession('key1');
        $I->seeInSession('key1', 'value1');
        $I->dontSeeInSession('missing');
        $I->dontSeeInSession('key1', 'other');
        $I->seeSessionHasValues(['key1', 'key2']);
        $I->seeSessionHasValues(['key1' => 'value1', 'key2' => 'value2']);
    }

    public function loginAndLogoutAssertions(FunctionalTester $I): void
    {
        $container = $I->grabService('service_container');
        $factory = $I->grabService('session.factory');
        $session = $factory->createSession();
        $session->start();
        $container->set('session', $session);

        /** @var UserRepository $repository */
        $repository = $I->grabService(UserRepository::class);
        $user = $repository->getByEmail('john_doe@gmail.com');

        $I->amLoggedInAs($user);
        $I->amOnPage('/dashboard');
        $I->seeAuthentication();
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $I->grabService('security.token_storage');
        $I->assertNotNull($tokenStorage->getToken());
        $I->see('You are in the Dashboard!');

        $token = new PostAuthenticationToken($user, 'main', $user->getRoles());
        $I->amLoggedInWithToken($token);
        $I->amOnPage('/dashboard');
        $I->seeAuthentication();
        $I->see('You are in the Dashboard!');

        $I->amLoggedInAs($user);
        $I->amOnPage('/dashboard');
        $I->see('You are in the Dashboard!');

        $I->goToLogoutPath();
        $I->seeCurrentRouteIs('index');
        $I->dontSeeAuthentication();

        $I->amLoggedInAs($user);
        $I->amOnPage('/dashboard');
        $I->see('You are in the Dashboard!');

        $I->logoutProgrammatically();
        $I->amOnPage('/dashboard');
        $I->seeInCurrentUrl('login');
        $I->dontSeeAuthentication();

        $session = $factory->createSession();
        $session->start();
        $container->set('session', $session);
        $I->amLoggedInAs($user);
        $I->amOnPage('/');
        $I->seeSessionHasValues(['_security_main', '_security_main']);
        $I->unpersistService('session');
    }

    public function dontSeeInSession(FunctionalTester $I): void
    {
        $factory = $I->grabService('session.factory');
        $session = $factory->createSession();
        $session->start();
        $I->grabService('service_container')->set('session', $session);

        $I->amOnPage('/');
        $I->dontSeeInSession('_security_main');
    }
}
