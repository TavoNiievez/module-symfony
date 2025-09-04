<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HelloCommand extends Command
{
    protected static $defaultName = 'app:hello';

    protected function configure(): void
    {
        $this->setDescription('Greets the user')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name to greet', 'World');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $output->writeln('Hello ' . $name);
        return Command::SUCCESS;
    }
}
