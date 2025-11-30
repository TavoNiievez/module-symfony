<?php

declare(strict_types=1);

namespace Tests\_app\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:example-command', 'An example command.')]
class ExampleCommand extends Command
{
    private const OPTION_SOMETHING = 'something';
    private const OPTION_SHORT_SOMETHING = 's';

    protected function configure(): void
    {
        $this->addOption(
            self::OPTION_SOMETHING,
            self::OPTION_SHORT_SOMETHING,
            InputOption::VALUE_NONE,
            'Give some output'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption(self::OPTION_SOMETHING)) {
            $io->text('Bye world!');
        } else {
            $io->text('Hello world!');
        }

        return Command::SUCCESS;
    }
}
