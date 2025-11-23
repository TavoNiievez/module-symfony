<?php

declare(strict_types=1);

namespace Tests;

/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
*/
class FunctionalTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;

    /**
     * Define custom actions here
     */
    public function registerUser(string $email, string $password, bool $followRedirects = true): void
    {
        $this->amOnPage('/register');

        if (!$followRedirects) {
            $this->stopFollowingRedirects();
        }

        $this->submitSymfonyForm('registration_form', [
            '[email]' => $email,
            '[password]' => $password,
            '[agreeTerms]' => true,
        ]);
    }
}
