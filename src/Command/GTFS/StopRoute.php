<?php

namespace App\Command\GTFS;

use App\Command\CommandFunctions;
use App\Service\DB;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StopRoute extends Command
{
    private EntityManagerInterface $entityManager;
    private DB $DB;

    public function __construct(EntityManagerInterface $entityManager, DB $DB)
    {
        $this->entityManager = $entityManager;
        $this->DB = $DB;

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
        $this->DB->TruncateTable($db, 'temp_stop_route');

        $output->writeln('> Generate Temp Stop Route 1/2...');
        $this->DB->generateTempStopRoute($db);

        $output->writeln('> Generate Temp Stop Route 2/2...');
        $this->DB->generateTempStopRoute2($db);

        // ---
        $output->writeln('> Updating Stop Route...');

        $this->DB->autoDeleteStopRoute($db);
        $this->DB->autoInsertStopRoute($db);

        return Command::SUCCESS;
    }
}