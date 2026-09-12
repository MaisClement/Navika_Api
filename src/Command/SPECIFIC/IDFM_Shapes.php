<?php

namespace App\Command\SPECIFIC;

use App\Controller\Functions;
use App\Entity\Shapes;
use App\Repository\ShapesRepository;
use App\Repository\ProviderRepository;
use CrEOF\Spatial\PHP\Types\Geometry\LineString;
use CrEOF\Spatial\PHP\Types\Geometry\Point;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;

class IDFM_Shapes extends Command
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private ShapesRepository $shapesRepository;
    private ProviderRepository $providerRepository;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params, ShapesRepository $shapesRepository, ProviderRepository $providerRepository)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->shapesRepository = $shapesRepository;
        $this->providerRepository = $providerRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:shapes:update')
            ->setDescription('Update shapes data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('...');

        // Get IDFM provider
        $provider = $this->providerRepository->find('IDFM');
        if (!$provider) {
            $output->writeln('<error>IDFM provider not found</error>');
            return Command::FAILURE;
        }

        // URLs for the datasets
        $railwayUrl = 'https://data.iledefrance-mobilites.fr/explore/dataset/traces-du-reseau-ferre-idf/download/?format=geojson';
        $busUrl = 'https://data.iledefrance-mobilites.fr/explore/dataset/traces-des-lignes-regulieres-de-bus-en-ile-de-france/download/?format=geojson';

        $client = HttpClient::create(['timeout' => 300]); // 5 minute timeout
        $processedShapes = [];
        $totalFeaturesProcessed = 0;
        $totalShapesInserted = 0;
        $totalPointsInserted = 0;
        $persistedSinceFlush = 0;
        $flushThreshold = 1000;
        
        // Process both datasets
        $datasets = [
            'railway' => $railwayUrl,
            'bus' => $busUrl
        ];

        // Remove old shapes for this provider
        $progressIndicator->setMessage('Removing old shapes...');

        $oldShapes = $this->shapesRepository->findBy(['provider_id' => $provider]);
        foreach ($oldShapes as $oldShape) {
            $progressIndicator->advance();
            $this->entityManager->remove($oldShape);
        }
        $this->entityManager->flush();

        $provider = $this->providerRepository->find('IDFM');

        foreach ($datasets as $type => $url) {
            $progressIndicator->setMessage("Downloading $type data...");
            
            try {
                $response = $client->request('GET', $url);
                $status = $response->getStatusCode();

                if ($status !== 200) {
                    $output->writeln("<error>Failed to download $type data. HTTP status: $status</error>");
                    continue;
                }

                $content = $response->getContent();
                $data = json_decode($content, true);

                if (!$data || !isset($data['features'])) {
                    $output->writeln("<error>Invalid GeoJSON data for $type</error>");
                    continue;
                }

                $progressIndicator->setMessage("Processing $type shapes...");

                $featuresCount = count($data['features']);

                $featuresProcessedForDataset = 0;
                foreach ($data['features'] as $feature) {
                    $progressIndicator->advance();
                    $featuresProcessedForDataset++;
                    $totalFeaturesProcessed++;

                    if ($type == 'railway') {
                        if (!isset($feature['properties']['idrefligc']) || !isset($feature['geometry']['coordinates'])) {
                            $output->writeln("<comment>Skipping railway feature without idrefligc or coordinates</comment>");
                            continue;
                        }
                        $lineId = $feature['properties']['idrefligc'];
                    }
                    if ($type == 'bus') {
                        if (!isset($feature['properties']['lineid']) || !isset($feature['geometry']['coordinates'])) {
                            $output->writeln("<comment>Skipping bus feature without lineid or coordinates</comment>");
                            continue;
                        }
                        $lineId = $feature['properties']['lineid'];
                    }

                    $lineId = 'IDFM:' . $lineId;

                    // Allow multiple shapes per same line by appending a counter suffix
                    // Build a base shape id and then ensure uniqueness by checking repository
                    $baseShapeId = $lineId;
                    $suffix = 1;
                    $shapeId = $baseShapeId . '_' . $suffix;

                    // If we've already processed an identical shape in this run, skip
                    // Otherwise, if shape exists in DB, increment suffix until unique
                    while (isset($processedShapes[$shapeId]) || $this->shapesRepository->findOneBy(['shape_id' => $shapeId, 'provider_id' => $provider])) {
                        // if processed in this run, skip to next candidate
                        if (isset($processedShapes[$shapeId])) {
                            $suffix++;
                            $shapeId = $baseShapeId . '_' . $suffix;
                            continue;
                        }

                        // shape exists in DB: try next suffix
                        $suffix++;
                        $shapeId = $baseShapeId . '_' . $suffix;
                    }

                    $processedShapes[$shapeId] = true;

                    $geometry = $feature['geometry'];
                    if ($geometry['type'] !== 'LineString' && $geometry['type'] !== 'MultiLineString') {
                        $output->writeln("<comment>Skipping geometry type {$geometry['type']} for $shapeId</comment>");
                        continue;
                    }

                    $coordinates = [];
                    if ($geometry['type'] === 'LineString') {
                        $coordinates = $geometry['coordinates'];
                    } elseif ($geometry['type'] === 'MultiLineString') {
                        // Pour MultiLineString, prenons la première ligne ou fusionnons toutes les lignes
                        $coordinates = [];
                        foreach ($geometry['coordinates'] as $lineString) {
                            $coordinates = array_merge($coordinates, $lineString);
                        }
                    }

                    if (empty($coordinates)) {
                        $output->writeln("<comment>No coordinates found for $shapeId</comment>");
                        continue;
                    }

                    // Convertir les coordonnées au format attendu par CrEOF ([lon, lat] -> [x, y])
                    $points = [];
                    foreach ($coordinates as $coord) {
                        if (!is_array($coord) || count($coord) < 2) {
                            continue;
                        }
                        // CrEOF attend le format [longitude, latitude] en string
                        $points[] = [(string)$coord[0], (string)$coord[1]];
                    }

                    if (empty($points)) {
                        $output->writeln("<comment>No valid points for $shapeId</comment>");
                        continue;
                    }

                    try {
                        $lineString = new LineString($points);
                        
                        $shape = new Shapes();
                        $shape->setProviderId($provider);
                        $shape->setShapeId($shapeId);
                        $shape->setLineId($lineId);
                        $shape->setGeometry($lineString);

                        $this->entityManager->persist($shape);
                        $totalShapesInserted++;
                        $totalPointsInserted += count($points);
                        $persistedSinceFlush++;

                    } catch (\Exception $e) {
                        $output->writeln("<error>Error creating LineString for $shapeId: " . $e->getMessage() . "</error>");
                        continue;
                    }

                    if ($persistedSinceFlush >= $flushThreshold) {
                        try {
                            $this->entityManager->flush();
                            $this->entityManager->clear();
                        } catch (\Exception $e) {
                            $output->writeln("<error>Flush failed: " . $e->getMessage() . "</error>");
                        }
                        // re-fetch provider after clear
                        $provider = $this->providerRepository->find('IDFM');
                        $persistedSinceFlush = 0;
                    }
                }
                
            } catch (\Exception $e) {
                $output->writeln("<error>Error processing $type: " . $e->getMessage() . "</error>");
                continue;
            }
        }

        $progressIndicator->setMessage('Saving data...');

        // Final flush pour les dernières données
        if ($persistedSinceFlush > 0) {
            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $output->writeln("<error>Final flush failed: " . $e->getMessage() . "</error>");
            }
        }

        $output->writeln("<info>Total features processed: $totalFeaturesProcessed</info>");
        $output->writeln("<info>Total shapes inserted: $totalShapesInserted</info>");
        $output->writeln("<info>Total points inserted: $totalPointsInserted</info>");

        $progressIndicator->finish('<info>✅ Shapes data updated successfully</info>');

        return Command::SUCCESS;
    }
}
