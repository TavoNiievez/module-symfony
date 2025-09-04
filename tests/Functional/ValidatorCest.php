<?php
namespace Tests\Functional;

use Symfony\Component\Validator\Constraints as Assert;
use Tests\FunctionalTester;

class ValidatorCest
{
    public function validatorAssertions(FunctionalTester $I): void
    {
        $invalid = new \ValidEntity();
        $valid = new \ValidEntity('John', 'abcd');

        $I->seeViolatedConstraint($invalid);
        $I->seeViolatedConstraint($invalid, 'name');
        $I->seeViolatedConstraint($invalid, 'short', Assert\Length::class);
        $I->seeViolatedConstraintsCount(2, $invalid);
        $I->seeViolatedConstraintsCount(1, $invalid, 'name');
        $I->seeViolatedConstraintMessage('too short', $invalid, 'short');
        $I->dontSeeViolatedConstraint($valid);
        $I->dontSeeViolatedConstraint($invalid, 'short', Assert\NotBlank::class);
    }
}
