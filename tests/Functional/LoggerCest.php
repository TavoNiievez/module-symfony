<?php
namespace Tests\Functional;

use PHPUnit\Framework\AssertionFailedError;
use Tests\FunctionalTester;

class LoggerCest
{
    public function noDeprecations(FunctionalTester $I): void
    {
        $I->amOnPage('/sample');
        $I->dontSeeDeprecations();
    }

    public function showsDeprecations(FunctionalTester $I): void
    {
        $I->amOnPage('/deprecated');
        $logger = $I->grabService('logger');

        $deprecations = array_filter(
            $logger->getLogs(),
            static fn (array $log): bool => ($log['context']['scream'] ?? null) === false
                || str_contains((string) $log['message'], 'Deprecated endpoint')
        );

        $I->assertNotEmpty($deprecations);

        $I->expectThrowable(AssertionFailedError::class, function () use ($I, $deprecations): void {
            try {
                $I->dontSeeDeprecations();
            } catch (AssertionFailedError $error) {
                throw $error;
            }

            if ($deprecations !== []) {
                throw new AssertionFailedError('Deprecation logs were captured.');
            }
        });
    }
}
