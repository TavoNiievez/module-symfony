<?php
namespace Tests\Functional;

use Symfony\Component\Validator\Constraints as Assert;
use Tests\FunctionalTester;

class ValidatorCest
{
    public function validatorAssertions(FunctionalTester $I): void
    {
        $valid = \ValidEntity::create('test@example.com', 'password123');

        $I->dontSeeViolatedConstraint($valid);
        $I->dontSeeViolatedConstraint($valid, 'email');
        $I->dontSeeViolatedConstraint($valid, 'email', Assert\Email::class);

        $invalidEmail = \ValidEntity::create('invalid_email', 'password123');
        $I->seeViolatedConstraint($invalidEmail);
        $I->seeViolatedConstraint($invalidEmail, 'email');

        $weakPassword = \ValidEntity::create('test@example.com', 'weak');
        $I->seeViolatedConstraint($weakPassword);
        $I->seeViolatedConstraint($weakPassword, 'password');
        $I->seeViolatedConstraint($weakPassword, 'password', Assert\Length::class);

        $I->seeViolatedConstraintsCount(2, \ValidEntity::create('invalid_email', 'weak'));
        $I->seeViolatedConstraintsCount(1, $weakPassword);
        $I->seeViolatedConstraintsCount(0, $weakPassword, 'email');

        $userWithBlankEmail = \ValidEntity::create('', 'weak');
        $I->seeViolatedConstraintMessage('valid email', $invalidEmail, 'email');
        $I->seeViolatedConstraintMessage('should not be blank', $userWithBlankEmail, 'email');
        $I->seeViolatedConstraintMessage('This value is too short', $userWithBlankEmail, 'email');
    }
}
