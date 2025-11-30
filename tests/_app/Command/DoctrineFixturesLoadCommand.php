<?php

declare(strict_types=1);

namespace Tests\_app\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('doctrine:fixtures:load', 'Load fixtures for testing')]
class DoctrineFixturesLoadCommand extends Command
{
    private static int $runs = 0;

    public static function reset(): void
    {
        self::$runs = 0;
    }

    public static function runs(): int
    {
        return self::$runs;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ++self::$runs;
        $output->writeln('Fixtures loaded');

        return Command::SUCCESS;
    }
}
