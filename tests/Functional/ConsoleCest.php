<?php
namespace Tests\Functional;

use Tests\FunctionalTester;

class ConsoleCest
{
    public function runCommand(FunctionalTester $I): void
    {
        $output = $I->runSymfonyConsoleCommand('app:example-command');
        $I->assertStringContainsString('Hello world!', $output);

        $output = $I->runSymfonyConsoleCommand('app:example-command', ['-s' => true]);
        $I->assertStringContainsString('Bye world!', $output);

        $output = $I->runSymfonyConsoleCommand('app:example-command', ['--something' => true]);
        $I->assertStringContainsString('Bye world!', $output);
    }

    public function runQuietCommand(FunctionalTester $I): void
    {
        \Tests\_app\DoctrineFixturesLoadCommand::reset();

        $output = $I->runSymfonyConsoleCommand('doctrine:fixtures:load', ['-q']);

        $I->assertSame('', $output);
        $I->assertSame(1, \Tests\_app\DoctrineFixturesLoadCommand::runs());
    }
}
