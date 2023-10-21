<?php

namespace App\Command\GTFS;

use App\Command\CommandFunctions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GTFS_StopRoute extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

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

        CommandFunctions::TruncateTable($db, 'temp_stop_route');
        CommandFunctions::generateTempStopRoute($db);

        // ---
        $output->writeln('> Updating Stop Route...');

        CommandFunctions::autoDeleteStopRoute($db);
        CommandFunctions::autoInsertStopRoute($db);

        return Command::SUCCESS;
    }
}