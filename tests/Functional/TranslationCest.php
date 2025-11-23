<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

final class TranslationCest
{
    public function dontSeeFallbackTranslations(FunctionalTester $I): void
    {
        $I->amOnPage('/register');
        $I->dontSeeFallbackTranslations();
    }

    public function dontSeeMissingTranslations(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->dontSeeMissingTranslations();
    }

    public function grabDefinedTranslationsCount(FunctionalTester $I): void
    {
        $I->amOnPage('/register');
        $I->assertSame(6, $I->grabDefinedTranslationsCount());
    }

    public function seeAllTranslationsDefined(FunctionalTester $I): void
    {
        $I->amOnPage('/register');
        $I->seeAllTranslationsDefined();
    }

    public function seeDefaultLocaleIs(FunctionalTester $I): void
    {
        $I->amOnPage('/register');
        $I->seeDefaultLocaleIs('en');
    }

    public function seeFallbackLocalesAre(FunctionalTester $I): void
    {
        $I->amOnPage('/register');
        $I->seeFallbackLocalesAre(['es']);
    }

    public function seeFallbackTranslationsCountLessThan(FunctionalTester $I): void
    {
        $I->amOnPage('/register');
        $I->seeFallbackTranslationsCountLessThan(1);
    }

    public function seeMissingTranslationsCountLessThan(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->seeMissingTranslationsCountLessThan(1);
    }
}
