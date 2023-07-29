<?php

namespace App\Command\Timetable;

use App\Controller\Functions;
use App\Entity\Timetable;
use App\Repository\RoutesRepository;
use App\Repository\TimetableRepository;
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
    private $entityManager;
    private $params;
    
    private RoutesRepository $routesRepository;
    private TimetableRepository $timetableRepository;
    
    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, TimetableRepository $timetableRepository, RoutesRepository $routesRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        
        $this->routesRepository = $routesRepository;
        $this->timetableRepository = $timetableRepository;
        
        parent::__construct();
    }
 
    protected function configure(): void
    {
        $this
            ->setName('app:timetable:update')
            ->setDescription('Update timetable data');
    }
    
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $file = $dir . '/timetable.csv';

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
                $type = $row[3] == 'HORAIRE' ? 'timetable' : 'map';

                $route = $this->routesRepository->findOneBy( ['route_id' => $route_id] );

                if ($route != null) {
                    $timetable = new Timetable();
                    $timetable->setRouteId( $route );
                    $timetable->setType( $type );
                    $timetable->setName( $row[1] );
                    $timetable->setUrl( $row[2] );
    
                    $this->entityManager->persist( $timetable );
                }
            }
        }      

        // On efface les messages existant
        $output->writeln('> Remove old...');
        
        $old_timetables = $this->timetableRepository->findAll();

        foreach ($old_timetables as $old_timetable) {
            $this->entityManager->remove($old_timetable);
        }
        
        // On sauvegarde
        $output->writeln('> Saving...');
                
        $this->entityManager->flush();
        
        $output->writeln('<info>✅ OK</info>');
        
        return Command::SUCCESS;
    }
}
