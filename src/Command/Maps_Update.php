<?php

namespace App\Command;

use App\Controller\Functions;
use App\Entity\Maps;
use App\Repository\RoutesRepository;
use App\Repository\MapsRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Console\Helper\ProgressIndicator;

class Maps_Update extends Command
{
    private $entityManager;
    private $params;

    private RoutesRepository $routesRepository;
    private MapsRepository $mapsRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, MapsRepository $mapsRepository, RoutesRepository $routesRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->routesRepository = $routesRepository;
        $this->mapsRepository = $mapsRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:maps:update')
            ->setDescription('Update maps data');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $file = $dir . '/maps.csv';

        // Récupération du trafic
        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Geting timetable...');

        $url = $this->params->get('prim_url_maps');

        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200) {
            return Command::FAILURE;
        }

        $content = $response->getContent();
        file_put_contents($file, $content);

        $content = Functions::readCsv($file);

        foreach ($content as $row) {
            // Loader
            $progressIndicator->advance();

            if (!is_bool($row) && $row[0] != 'id') {
                $maps = new Maps();
                $maps->setName($row[2]);
                $maps->setUrl($row[4]);
                $maps->setNumber(intval($row[1]));

                $this->entityManager ->persist($maps);
            }
        }

        // On efface les messages existant
        $progressIndicator->setMessage('Removing old maps...');

        $old_maps = $this->mapsRepository->findAll();

        foreach ($old_maps as $old_maps) {
            // Loader
            $progressIndicator->advance();

            $this->entityManager->remove($old_maps);
        }

        // On sauvegarde
        $progressIndicator->setMessage('Saving data...');

        $this->entityManager->flush();

        $progressIndicator->finish('<info>✅ OK</info>');

        return Command::SUCCESS;
    }
}