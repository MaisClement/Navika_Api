<?php

namespace App\Command\Motis;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Ancienne commande de construction des données MOTIS.
 *
 * Elle écrasait build/data en place, donc pendant que l'instance en service
 * lisait ce répertoire : c'est ce qui rendait les mises à jour risquées.
 * app:motis:deploy construit désormais chaque jeu de données à part et ne
 * bascule qu'une fois la nouvelle instance vérifiée.
 *
 * Conservée pour ne pas casser les appels existants ; elle délègue.
 */
class Build extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('app:motis:build')
            ->setDescription('[obsolète] Utiliser app:motis:deploy');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<comment>app:motis:build est obsolète : app:motis:deploy construit les données et bascule sans coupure.</comment>');
        $output->writeln('<comment>Exécution de app:motis:deploy à la place.</comment>');

        return $this->getApplication()->doRun(new ArrayInput(['command' => 'app:motis:deploy']), $output);
    }
}
