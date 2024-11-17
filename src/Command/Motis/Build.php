<?php

namespace App\Command\Motis;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Helper\ProgressIndicator;
use App\Service\Logger;

class Build extends Command
{
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
        ->setName('app:motis:build')
        ->setDescription('Start Motis');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $cmd_log = $dir . '/navika/motis_build.log';
        file_put_contents($cmd_log, '');

        $motis_path = $this->params->get('motis_path');
        chdir($motis_path . '/build');

        $command = './motis import -c config.yml > ' . $cmd_log;

        $this->logger->log(['message' => '[motis][build] Starting motis build...']);
        $output->writeln('[motis][build] Starting motis build...');
        exec($command);

        $this->logger->log(['message' => '[motis] Motis is now ready']);
        $output->writeln('[motis] Motis is now ready');
                

        return Command::SUCCESS;
    }
}