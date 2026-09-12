<?php

namespace App\Command\SPECIFIC;

use App\Controller\Functions;
use App\Entity\Maps;
use App\Repository\MapsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;

class IDFM_Maps extends Command
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private MapsRepository $mapsRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, MapsRepository $mapsRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->mapsRepository = $mapsRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:maps:update')
            ->setDescription('Update maps data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $file = $dir . '/maps.csv';

        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Getting timetable...');

        $url = $this->params->get('prim_url_maps');

        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status !== 200) {
            return Command::FAILURE;
        }

        $content = $response->getContent();
        file_put_contents($file, $content);

        $content = Functions::readCsv($file);

        foreach ($content as $row) {
            $progressIndicator->advance();

            if (!is_bool($row) && $row[0] !== 'id') {
                $map = new Maps();
                $map->setName($row[2]);
                $map->setUrl($row[4]);
                $map->setNumber((int) $row[1]);

                $this->entityManager->persist($map);
            }
        }

        $progressIndicator->setMessage('Removing old maps...');

        $oldMaps = $this->mapsRepository->findAll();

        foreach ($oldMaps as $oldMap) {
            $progressIndicator->advance();
            $this->entityManager->remove($oldMap);
        }

        $progressIndicator->setMessage('Saving data...');

        $this->entityManager->flush();

        $progressIndicator->finish('<info>✅ OK</info>');

        return Command::SUCCESS;
    }
}
