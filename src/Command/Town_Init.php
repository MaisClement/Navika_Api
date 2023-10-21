<?php

namespace App\Command;

use App\Entity\Town;
use CrEOF\Spatial\PHP\Types\Geography\Polygon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Town_Init extends Command
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
            ->setName('app:init:town')
            ->setDescription('Import ZipCode from JSON file')
            ->addArgument('townfile', InputArgument::REQUIRED, 'JSON file to import')
            ->addArgument('zipcode', InputArgument::REQUIRED, 'JSON file to import');
    }
    
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('townfile');
        $json = file_get_contents($file);
        $towns = json_decode($json, true);

        $file = $input->getArgument('zipcode');
        $json = file_get_contents($file);
        $zip_codes = json_decode($json, true);

        // ---

        $progressIndicator = new ProgressIndicator($output, 'verbose', 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Importing cities into the database...');

        foreach ($towns['features'] as $feature) {
            try {
                $name = $feature['properties']['nom'];
                $id = $feature['properties']['code'];

                if ($feature['geometry']['type'] == 'Polygon') {

                    // Loader
                    $progressIndicator->advance();

                    $polygon = new Polygon($feature['geometry']['coordinates']);
    
                    foreach($zip_codes as $code) {
                        if($code['id'] == $id) {
                            $zip_code = $code['zip_code'];
                            break;
                        }
                    }
        
                    $town = new Town();
                    $town->setTownId($id);
                    $town->setTownName($name);
                    $town->setTownPolygon($polygon);
                    $town->setZipCode($zip_code ?? '');
        
                    $this->entityManager->persist($town);
                }
            } catch (\Exception $e) {
                echo $name;
            }      
        }
        
        $progressIndicator->setMessage('Saving data...');
        
        $this->entityManager->flush();
        
        $progressIndicator->finish('  OK ✅');
        
        return Command::SUCCESS;
    }
}
