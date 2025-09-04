<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class TranslationCest
{
    public function translationAssertions(FunctionalTester $I): void
    {
        $I->amOnRoute('translation');
        $I->dontSeeMissingTranslations();
        $I->dontSeeFallbackTranslations();
        $I->assertGreaterThanOrEqual(0, $I->grabDefinedTranslationsCount());
        $I->seeAllTranslationsDefined();
        $I->seeDefaultLocaleIs('en');
        $I->seeFallbackLocalesAre(['es']);
        $I->seeFallbackTranslationsCountLessThan(1);
        $I->seeMissingTranslationsCountLessThan(1);
    }
}
