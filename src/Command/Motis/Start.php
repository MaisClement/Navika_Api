<?php

namespace App\Command\Motis;

use App\Entity\MotisInstances;
use App\Repository\MotisInstancesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Helper\ProgressIndicator;
use App\Service\Logger;

class Start extends Command
{
    private EntityManagerInterface $entityManager;

    private MotisInstancesRepository $motisInstancesRepository;

    private ParameterBagInterface $params;
    private Logger $logger;

    public function __construct(EntityManagerInterface $entityManager, MotisInstancesRepository $motisInstancesRepository, ParameterBagInterface $params,  Logger $logger)
    {
        $this->entityManager = $entityManager;

        $this->motisInstancesRepository = $motisInstancesRepository;

        $this->params = $params;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
        ->setName('app:motis:start')
        ->setDescription('Start a motis instance')
        ->addArgument('id', InputArgument::REQUIRED, 'id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        
        // Check if instance is not already registered
        $instance = $this->motisInstancesRepository->Find($id);
        if ($instance == null) {
           $output->writeln('<warning>Unknow instance id</warning>');
            return Command::FAILURE;
        }

        // Check if the instance is not already running
        $pid = $instance->getPid();
        if ($pid != null && $this->isRunning($pid)) {
            $this->logger->log(['message' => '[motis]['. $id .'] Motis is already running.']);
            $output->writeln('[motis]['. $id .'] Motis is already running.');
            return Command::SUCCESS;
        }

        // Prepare pid and log file
        $dir = sys_get_temp_dir() . '/navika';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $cmd_log = $dir . '/motis_'. $id .'.log';
        $pid_file = $dir . '/motis_'. $id .'.pid';
        file_put_contents($cmd_log, '');
        file_put_contents($pid_file, '');


        // Go to Motis instance path
        $motis_path = $this->params->get('motis_path') . '/' . $id;
        chdir($motis_path);

        $command = './motis server';
        exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $command, $cmd_log, $pid_file));


        // Save the PID
        $pid = file_get_contents($pid_file);
        $pid = trim( $pid );

        $instance->setPid($pid);
        $instance->setState("starting");    // "unknown" "killed" "starting" "running"
        $this->entityManager->flush();
            

        $this->logger->log(['message' => '[motis]['. $id .'] Starting motis...']);
        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('['. $id .'] Starting motis...');

        while (true) {
            sleep(.2);
            $progressIndicator->advance();

            if (!$this->isRunning($pid)) {
                $this->logger->log(['message' => '[motis]['. $id .'] Motis fail to start.']);
                $output->writeln('[motis]['. $id .'] Motis fail to start.');
                return Command::FAILURE;
            }

            $log_content = file_get_contents($cmd_log);
            if (strpos($log_content, 'listening on 0.0.0.0') !== false) {
                break;
            }
        }

        // Save the PID
        $instance->setPid($pid);
        $instance->setState("running");    // "unknown" "stoped" "killed" "starting" "running"
        $this->entityManager->flush();

        $progressIndicator->finish('  OK ✅');
        $this->logger->log(['message' => '[motis]['. $id .'] Motis is now ready']);
        $output->writeln('[motis]['. $id .'] Motis is now ready');
                

        return Command::SUCCESS;
    }

    private function isRunning($pid): bool
    {
        try {
            $result = shell_exec(sprintf("ps %d", $pid));
            if (count(preg_split("/\n/", $result)) > 2) {
                return true;
            }
        } catch (Exception $e) {}

        return false;
    }
}