<?php

namespace App\Command\Provider;

use App\Entity\Provider;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Add extends Command
{
    private EntityManagerInterface $entityManager;

    private ProviderRepository $providerRepository;

    private HttpClientInterface $httpClient;

    public function __construct(EntityManagerInterface $entityManager, ProviderRepository $providerRepository, HttpClientInterface $httpClient )
    {
        $this->entityManager = $entityManager;

        $this->providerRepository = $providerRepository;
        $this->httpClient = $httpClient;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:provider:add')
            ->setDescription('Add a data provider. Usage:
  1) Legacy: app:provider:add <type> <id> <name> <area> <datagouv_id>
  2) Simplifié: app:provider:add "Titre du dataset" (ex: "Réseau urbain Compagnie d\'Autobus de Monaco")')
            ->addArgument('args', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Either: [type id name area datagouv_id] or [dataset title]');
    }

    function execute(InputInterface $input, OutputInterface $output): int
    {
        $args = $input->getArgument('args');

        $type = $id = $name = $area = $url = null;

        if (count($args) === 1) {
            // Mode automatique sur titre dataset
            $queryTitle = trim($args[0]);
            try {
                $response = $this->httpClient->request('GET', 'https://transport.data.gouv.fr/api/datasets/');
                $datasets = $response->toArray();
            } catch (\Throwable $e) {
                $output->writeln('<fg=red>❌ Erreur requête API transport.data.gouv.fr: ' . $e->getMessage() . '</>');
                return Command::FAILURE;
            }

            $matches = [];
            foreach ($datasets as $dataset) {
                if (!isset($dataset['title'])) continue;
                if (stripos($dataset['title'], $queryTitle) !== false || stripos($queryTitle, $dataset['title']) !== false) {
                    $matches[] = $dataset;
                }
            }

            if (count($matches) === 0) {
                $output->writeln('<fg=red>❌ Aucun dataset correspondant à: ' . $queryTitle . '</>');
                return Command::FAILURE;
            }
            if (count($matches) > 1) {
                $output->writeln('<fg=yellow>⚠️ Plusieurs datasets trouvés, soyez plus précis:</>');
                foreach ($matches as $d) {
                    $output->writeln(' - ' . $d['title']);
                }
                return Command::FAILURE;
            }

            $dataset = $matches[0];

            // type mapping
            $mapType = [
                'public-transit' => 'tc',
                'vehicles-sharing' => 'bikes',
            ];
            $type = $mapType[$dataset['type']] ?? ($dataset['type'] ?? 'tc');

            $url = $dataset['datagouv_id'] ?? '';
            $area = $dataset['covered_area'][0]['nom'] ?? 'Unknown';

            $baseTitle = $dataset['title'];

            // Chercher un code réseau via metadata.networks si présent
            $networkCode = null;
            if (!empty($dataset['resources']) && is_array($dataset['resources'])) {
                foreach ($dataset['resources'] as $res) {
                    if (isset($res['metadata']['networks']) && is_array($res['metadata']['networks']) && count($res['metadata']['networks']) > 0) {
                        $networkCode = $res['metadata']['networks'][0];
                        break;
                    }
                }
            }

            // Extraire nom simplifié si pattern "Réseau urbain ..."
            $baseName = $baseTitle;
            if (preg_match('/Réseau urbain\s+(.+)/i', $baseTitle, $m)) {
                $baseName = $m[1];
            }
            if ($networkCode) {
                $baseName = $networkCode;
            }

            // Normalisation du nom d'affichage
            $tmpName = preg_replace("/\bd['’]\s*/i", '', $baseName); // retire d'
            $tmpName = preg_replace("/['’]/", '', $tmpName); // retire quotes restantes
            $tmpName = preg_replace('/\bDES\b/i', ' DE ', $tmpName); // remplace DES par DE
            $tmpName = preg_replace('/\s+/', ' ', trim($tmpName));
            $name = strtoupper($tmpName);

            // Construction de l'identifiant: 1er mot + concat des suivants, en conservant DE mais sans espaces
            $idParts = preg_split('/\s+/', $name);
            $idParts = array_values(array_filter($idParts, fn($p) => $p !== ''));
            // Règle spéciale: si le 2e mot est DE après le premier (ex: COMPAGNIE DE AUTOBUS DE MONACO), on retire ce DE
            if (count($idParts) > 2 && strtoupper($idParts[1]) === 'DE') {
                array_splice($idParts, 1, 1);
                $name = strtoupper(implode(' ', $idParts));
            }
            if (count($idParts) > 1) {
                $first = array_shift($idParts);
                $remainingId = '';
                foreach ($idParts as $w) {
                    $remainingId .= strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $w));
                }
                $id = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $first)) . $remainingId;
            } else {
                $id = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
            }

            $output->writeln('<info>🛠 Mode automatique</info>');
            $output->writeln('  Type: ' . $type);
            $output->writeln('  Id: ' . $id);
            $output->writeln('  Name: ' . $name);
            $output->writeln('  Area: ' . $area);
            $output->writeln('  DataGouv Id: ' . $url);
        } elseif (count($args) === 5) {
            // Mode legacy
            [$type, $id, $name, $area, $url] = $args;
        } else {
            $output->writeln('<fg=red>❌ Nombre d’arguments invalide. Fournissez soit 1 argument (titre dataset) soit 5 arguments (type id name area datagouv_id).</>');
            return Command::FAILURE;
        }

        $flag = '0';

        // Check if provider is not already registered
        $providers = $this->providerRepository->Find($id);
        if ($providers instanceof \App\Entity\Provider) {
            $output->writeln('<fg=blue>ℹ️ Provider already registered</>');
            return Command::SUCCESS;
        }

        // ---

        $provider = new Provider();
    $provider->setId($id);
    $provider->setType($type);
    $provider->setName($name);
    $provider->setArea($area);
    $provider->setUrl($url);
        $provider->setFlag($flag);

        // if it's a sub provider, check if parent provider exist
        if (strpos($id, ":") !== false) {
            $parent_id = substr($id, 0, strpos($id, ":"));
            $providers = $this->providerRepository->Find($parent_id);
            if ($providers instanceof \App\Entity\Provider) {
                $provider->setParentProvider($parent_id);
            } else {
                $output->writeln('<fg=red>❌ Couldn’t find ' . $parent_id . ' parent provider</>');
                return Command::SUCCESS;
            }
        }

        $this->entityManager->persist($provider);

        $this->entityManager->flush();

        $output->writeln('<info>✅ New provider added successfully</info>');

        return Command::SUCCESS;
    }
}