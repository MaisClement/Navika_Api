<?php

namespace App\Command;

use App\Entity\Agency;
use App\Entity\Provider;
use App\Entity\Routes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitSNCF extends Command
{
    private \Doctrine\ORM\EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }
 
    protected function configure(): void
    {
        $this
            ->setName('app:init:sncf')
            ->setDescription('Init for SNCF');
    }
    
    function execute(InputInterface $input, OutputInterface $output): int
    {        
        $output->writeln('> Initialisation...');
        
        $provider = new Provider();
        $provider->setId('SNCF');
        $provider->setType('rail');
        $provider->setName('SNCF');
        $provider->setArea('France');
        $provider->setUrl('https://ressources.data.sncf.com/explore/dataset/referentiel-gares-voyageurs/download/?format=csv&timezone=Europe/Berlin&lang=fr');
        $provider->setFlag('0');

        $this->entityManager->persist($provider);

        $agency = new Agency();
        $agency->setProviderId($provider);
        $agency->setAgencyId('SNCF');
        $agency->setAgencyName('SNCF');
        $agency->setAgencyUrl('https://www.sncf.com');
        $agency->setAgencyTimezone('Europe/Paris');

        $this->entityManager->persist($agency);

        $route = new Routes();
        $route->setProviderId($provider);
        $route->setAgencyId($agency);
        $route->setRouteId('SNCF');
        $route->setRouteShortName('SNCF');
        $route->setRouteLongName('Trains SNCF');
        $route->setRouteType('99');
        $route->setRouteColor('aaaaaa');
        $route->setRouteTextColor('000000');
        $route->setContinuousPickup('1');

        $this->entityManager->persist($route);
        
        $output->writeln('> Ecriture...');
        
        $this->entityManager->flush();
        
        $output->writeln('  OK ✅');
        
        return Command::SUCCESS;
    }
}
