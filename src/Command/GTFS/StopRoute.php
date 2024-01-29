<?php

namespace App\Command\GTFS;

use App\Command\CommandFunctions;
use App\Service\DBServices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StopRoute extends Command
{
    private $entityManager;
    private DBServices $dbServices;

    public function __construct(EntityManagerInterface $entityManager, DBServices $dbServices)
    {
        $this->entityManager = $entityManager;
        $this->dbServices = $dbServices;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:gtfs:stoproute')
            ->setDescription('Update gtfs');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();

        // --

        $output->writeln('> Generate Temp Stop Route...');

        $this->dbServices->TruncateTable($db, 'temp_stop_route');
        $this->dbServices->generateTempStopRoute($db);

        // ---
        $output->writeln('> Updating Stop Route...');

        $this->dbServices->autoDeleteStopRoute($db);
        $this->dbServices->autoInsertStopRoute($db);

        return Command::SUCCESS;
    }
}