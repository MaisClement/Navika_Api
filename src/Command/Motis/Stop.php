<?php

namespace App\Command\Motis;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Stop extends Command
{
    protected static $defaultName = 'app:motis:stop';

    protected function configure(): void
    {
        $this
            ->setDescription('Arrête Motis');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Assurez-vous de stocker le PID du processus Motis lorsque vous le démarrez
        $pid = file_get_contents('path/to/motis_pid_file');

        if ($pid) {
            $process = new Process(['kill', $pid]);
            $process->run();

            if (!$process->isSuccessful()) {
                $output->writeln('Impossible d\'arrêter Motis.');
                return Command::FAILURE;
            }

            $output->writeln('Motis a été arrêté avec succès.');
            return Command::SUCCESS;
        }

        $output->writeln('PID de Motis introuvable.');
        return Command::FAILURE;
    }
}