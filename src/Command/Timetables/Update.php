<?php

namespace App\Command\Timetables;

use App\Controller\Functions;
use App\Entity\Timetables;
use App\Repository\RoutesRepository;
use App\Repository\TimetablesRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;

// php bin/console app:init:zip_code /var/www/navika/data/file/communes.geojson /var/www/navika/data/file/zip_code.json

class Update extends Command
{
    private \Doctrine\ORM\EntityManagerInterface $entityManager;
    private \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $params;
    
    private RoutesRepository $routesRepository;
    private TimetablesRepository $timetablesRepository;
    
    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, TimetablesRepository $timetablesRepository, RoutesRepository $routesRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        
        $this->routesRepository = $routesRepository;
        $this->timetablesRepository = $timetablesRepository;
        
        parent::__construct();
    }
 
    protected function configure(): void
    {
        $this
            ->setName('app:timetables:update')
            ->setDescription('Update timetables data');
    }
    
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $file = $dir . '/timetables.csv';

        // Récupération du trafic
        $output->writeln('> Geting timetables...');

        $url = $this->params->get('prim_url_timetables');
        
        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200){
            return Command::FAILURE;
        }

        $content = $response->getContent();
        file_put_contents($file, $content);

        $content = Functions::readCsv($file);

        foreach ($content as $row) {
            if ( !is_bool($row) && $row[0] != 'ID_Line') {
                
                $route_id = 'IDFM:' . $row[0];
                $type = $row[3] == 'HORAIRE' ? 'timetables' : 'map';

                $route = $this->routesRepository->findOneBy( ['route_id' => $route_id] );

                if ($route != null) {
                    $timetables = new Timetables();
                    $timetables->setRouteId( $route );
                    $timetables->setType( $type );
                    $timetables->setName( $row[1] );
                    $timetables->setUrl( $row[2] );
    
                    $this->entityManager->persist( $timetables );
                }
            }
        }      

        // On efface les messages existant
        $output->writeln('> Remove old...');
        
        $old_timetables = $this->timetablesRepository->findAll();

        foreach ($old_timetables as $old_timetables) {
            $this->entityManager->remove($old_timetables);
        }
        
        // On sauvegarde
        $output->writeln('> Saving...');
                
        $this->entityManager->flush();
        
        $output->writeln('<info>✅ OK</info>');
        
        return Command::SUCCESS;
    }
}
