<?php

namespace App\Command\GTFS;

use App\Entity\Stops;
use App\Repository\StopsRepository;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConcatStopArea extends Command
{
    private $entityManager;

    private StopsRepository $stopsRepository;
    private ProviderRepository $providerRepository;

    public function __construct(EntityManagerInterface $entityManager, ProviderRepository $providerRepository, StopsRepository $stopsRepository)
    {
        $this->entityManager = $entityManager;

        $this->stopsRepository = $stopsRepository;
        $this->providerRepository = $providerRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:gtfs:concatstoparea')
            ->setDescription('Update gtfs');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();

        // --
        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Concat Stop Area...');
        // ----

        $new_stops = [];
        $providers = $this->providerRepository->FindBy(['type' => 'tc']);

        foreach($providers as $provider) {
            if ($provider->getParentProvider() != "" && $provider->getParentProvider() != null) {

                $parent_provider = $this->providerRepository->FindOneBy(['id' => $provider->getParentProvider()]);
                
                $stops = $this->stopsRepository->FindBy(['location_type' => '1', 'provider_id' => $provider->getId()]);
                
                foreach($stops as $stop) {
                    $progressIndicator->advance();

                    $id = $stop->getStopId();
                    $id = str_replace( $provider->getId(), $parent_provider->getId(), $id );

                    if ( !isset($new_stops[$id]) ) {
                        $new_stop = clone $stop;
                        $new_stop->setStopId( $id );
                        $new_stop->setProviderId( $parent_provider );
                        
                        $this->entityManager->persist($new_stop);

                        $new_stops[$id] = $new_stop;
                    }
                    $stop->setLocationType( '0' );
                    $stop->setParentStation( $id );

                    $sub_stops = $this->stopsRepository->FindBy(['parent_station' => $stop->getStopId()]);
                    foreach($sub_stops as $sub_stop) {
                        $sub_stop->setParentStation( $id );
                    }
                }

            }
        }

        $this->entityManager->flush();

        // Loader
        $progressIndicator->finish('✅ OK');

        return Command::SUCCESS;
    }
}