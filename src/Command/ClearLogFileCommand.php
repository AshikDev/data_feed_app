<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ClearLog'
)]
class ClearLogFileCommand extends Command
{
    protected static $defaultName = 'app:clear-log';
    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this->setDescription('Clean Error Log File');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $logFilePath = 'var/log/error.log';
        if (file_exists($logFilePath)) {
            file_put_contents($logFilePath, '');
            $io->success('Log file cleared.');
        } else {
            $io->error('Log file not found.');
        }

        return Command::SUCCESS;
    }
}
