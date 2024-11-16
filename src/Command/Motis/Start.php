<?php

namespace App\Command\Motis;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Service\Logger;

class Start extends Command
{
    protected static $defaultName = 'app:motis:start';
    private ParameterBagInterface $params;
    private Logger $logger;

    public function __construct(ParameterBagInterface $params,  Logger $logger)
    {
        $this->params = $params;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Start Motis');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        
        $process = new Process(['path/to/motis_executable']);
        $process->start();

        if (!$process->isRunning()) {
            $this->logger->error('Motis failed to start.');
            throw new ProcessFailedException($process);
        }

        // Save the process PID
        $pid = $process->getPid();
        $pid_file = $dir . '/navika/motis_blue.pid';
        file_put_contents($pid_file, $pid);

        $this->logger->log(['message' => '[motis] Motis has been started successfully.']);
        $output->writeln('Motis a été démarré avec succès.');

        return Command::SUCCESS;
    }
}