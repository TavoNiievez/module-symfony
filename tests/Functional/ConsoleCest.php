<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class ConsoleCest
{
    public function runCommand(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand('app:hello', ['name' => 'Codeception']);
        $I->assertStringContainsString('Hello Codeception', $output);
    }
}
