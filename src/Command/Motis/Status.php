<?php

namespace App\Command\Motis;

use App\Repository\MotisInstancesRepository;
use App\Service\Motis;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Vue d'ensemble des instances MOTIS.
 */
class Status extends Command
{
    private MotisInstancesRepository $motisInstancesRepository;
    private Motis $motis;

    public function __construct(MotisInstancesRepository $motisInstancesRepository, Motis $motis)
    {
        $this->motisInstancesRepository = $motisInstancesRepository;
        $this->motis = $motis;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:motis:status')
            ->setDescription('Affiche l\'état des instances motis')
            ->addOption('probe', 'p', InputOption::VALUE_NONE, 'Sonde les instances au lieu de lire l\'état enregistré');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $instances = $this->motisInstancesRepository->findAll();

        if ($instances === []) {
            $output->writeln('<comment>Aucune instance enregistrée (app:motis:add)</comment>');

            return Command::SUCCESS;
        }

        $probe = (bool) $input->getOption('probe');
        $currentHash = $this->motis->computeDataHash();

        $table = new Table($output);
        $table->setHeaders(['Instance', 'Port', 'État', 'Actif', 'Sain', 'PID', 'Données', 'À jour', 'Vérifié']);

        foreach ($instances as $instance) {
            $healthy = $probe
                ? $this->motis->probe($instance)['ok']
                : $instance->isHealthy();

            $table->addRow([
                $instance->getId(),
                $instance->getPort(),
                $instance->getState(),
                $instance->isActive() ? '<info>oui</info>' : 'non',
                $healthy ? '<info>oui</info>' : '<error>non</error>',
                $this->motis->isProcessRunning($instance->getPid()) ? $instance->getPid() : '-',
                $instance->getDataPath() === null ? '-' : basename($instance->getDataPath()),
                $instance->getDataHash() === null ? '-' : ($instance->getDataHash() === $currentHash ? 'oui' : '<comment>non</comment>'),
                $instance->getCheckedAt()?->format('Y-m-d H:i:s') ?? '-',
            ]);
        }

        $table->render();

        $resolved = $this->motis->resolve();
        if ($resolved === null) {
            $output->writeln('<error>Aucune instance ne peut servir de trafic — /motis/journeys renverra 503</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>Les requêtes /motis/journeys sont routées vers %s (%s)</info>',
            $resolved->getId(),
            $this->motis->getBaseUrl($resolved)
        ));

        foreach ($instances as $instance) {
            if ($instance->getLastError() !== null && !$instance->isHealthy()) {
                $output->writeln(sprintf('  %s : %s', $instance->getId(), $instance->getLastError()));
            }
        }

        return Command::SUCCESS;
    }
}
