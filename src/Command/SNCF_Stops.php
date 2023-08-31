<?php

namespace App\Command;

use App\Controller\Functions;
use App\Entity\StopRoute;
use App\Entity\Stops;
use App\Repository\ProviderRepository;
use App\Repository\RoutesRepository;
use App\Repository\StopsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SNCF_Stops extends Command
{
    private $entityManager;
    private $params;

    private ProviderRepository $providerRepository;
    private StopsRepository $stopsRepository;
    private RoutesRepository $routesRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, ProviderRepository $providerRepository, StopsRepository $stopsRepository, RoutesRepository $routesRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;

        $this->providerRepository = $providerRepository;
        $this->stopsRepository = $stopsRepository;
        $this->routesRepository = $routesRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:sncf:update')
            ->setDescription('Update sncf');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = sys_get_temp_dir();
        $db = $this->entityManager->getConnection();

        // --
        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Import SNCF stops...');
        // --

        // ---

        $SNCF_FORBIDDEN_DEPT = array("75", "92", "93", "94", "77", "78", "91", "95");
        $SNCF_FORCE = array("Bréval", "Gazeran", "Angerville", "Monnerville", "Guillerval");
        $SNCF_FORBIDDEN = array("Crépy-en-Valois", "Château-Thierry", "Montargis", "Malesherbes", "Dreux", "Gisors", "Creil", "Le Plessis-Belleville", "Nanteuil-le-Haudouin ", "Ormoy-Villers", "Mareuil-sur-Ourcq", "La Ferté-Milon", "Nogent-l'Artaud - Charly", "Dordives", "Ferrières - Fontenay", "Marchezais - Broué", "Vernon - Giverny", "Trie-Château", "Chaumont-en-Vexin", "Liancourt-Saint-Pierre", "Lavilletertre", "Boran-sur-Oise", "Précy-sur-Oise", "Saint-Leu-d'Esserent", "Chantilly - Gouvieux", "Orry-la-Ville - Coye", "La Borne Blanche");

        // Loader
        $progressIndicator->advance();

        $provider = $this->providerRepository->FindOneBy(['id' => 'SNCF']);
        if ($provider == null) {
            $output->writeln('<error>Unknow provider</error>');
            return Command::FAILURE;
        }

        // On supprime les arrêts deja existant
        $stops = $provider->getStops();
        foreach ($stops as $stop) {
            $progressIndicator->advance();
            $this->entityManager->remove($stop);
        }

        $this->entityManager->flush();

        $url = $provider->getUrl();
        $id = $provider->getId();
        $file = $dir . '/' . $id . '.csv';

        // $output->writeln('    ' . $url);
        $sncf = file_get_contents($url);
        file_put_contents($file, $sncf);

        // Loader
        $progressIndicator->advance();

        $route = $this->routesRepository->FindOneBy(['route_id' => 'SNCF']);

        // Loader
        $progressIndicator->advance();

        $s = [];
        $sncf = Functions::readCsv($file);

        // Loader
        $progressIndicator->advance();

        foreach ($sncf as $row) {
            // Loader
            $progressIndicator->advance();
            if (!is_bool($row) && $row[0] != 'code' && $row[1] != '' && $row[1] != false) {

                $id = 'SNCF:' . substr($row[2], 2);

                if (!in_array($id, $s)) {
                    $s[] = $id;

                    $allowed = true;
                    if (in_array($row[8], $SNCF_FORBIDDEN_DEPT)) {
                        $allowed = false;
                    }
                    if (in_array($row[4], $SNCF_FORBIDDEN)) {
                        $allowed = false;
                    }
                    if (in_array($row[4], $SNCF_FORCE)) {
                        $allowed = true;
                    }

                    if ($allowed == true) {
                        try {

                            $stop = $this->stopsRepository->findOneBy(['stop_id' => $id]);
                            if ($stop == null) {
                                $stop = new Stops();
                                $stop->setProviderId($provider);
                                $stop->setStopId($id);
                                $stop->setStopCode($row[27]);
                                $stop->setStopName($row[4]);
                                $stop->setStopLat(isset($row[11]) ? $row[11] : '');
                                $stop->setStopLon(isset($row[10]) ? $row[10] : '');
                                $stop->setLocationType('0');
                                $stop->setVehicleType('2');
                            }

                            $stop_route = new StopRoute();
                            $stop_route->setRouteKey('SNCF-' . $id);
                            $stop_route->setRouteId($route);
                            $stop_route->setRouteShortName($route->getRouteShortName());
                            $stop_route->setRouteLongName($route->getRouteLongName());
                            $stop_route->setRouteType($route->getRouteType());
                            $stop_route->setRouteColor($route->getRouteColor());
                            $stop_route->setRouteTextColor($route->getRouteTextColor());
                            $stop_route->setStopId($stop);
                            $stop_route->setStopName($stop->getStopName());
                            $stop_route->setStopQueryName($stop->getStopName());
                            $stop_route->setStopLat($stop->getStopLat());
                            $stop_route->setStopLon($stop->getStopLon());

                            $this->entityManager->persist($stop);
                            $this->entityManager->persist($stop_route);
                        } catch (\Exception $e) {
                            echo $e;
                        }
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