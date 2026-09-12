<?php

namespace App\Command\DB;

use App\Service\DB;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Etat des compteurs AUTO_INCREMENT de la base.
 *
 * Sert à repérer les tables dont les id approchent la capacité de leur colonne
 * avant que les insertions échouent. Renvoie un code d'erreur au-delà du seuil,
 * pour pouvoir être branché sur la supervision.
 */
class IdsStatus extends Command
{
    private EntityManagerInterface $entityManager;
    private DB $DB;

    public function __construct(EntityManagerInterface $entityManager, DB $DB)
    {
        $this->entityManager = $entityManager;
        $this->DB = $DB;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:db:ids:status')
            ->setDescription('Affiche l\'occupation des compteurs AUTO_INCREMENT')
            ->addOption('threshold', 't', InputOption::VALUE_REQUIRED, 'Seuil d\'alerte en % du type de colonne', '50')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Affiche toutes les tables, pas seulement celles au-dessus du seuil');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = $this->entityManager->getConnection();
        $threshold = (float) $input->getOption('threshold');
        $showAll = (bool) $input->getOption('all');

        $status = $this->DB->getAutoIncrementStatus($db);
        $over = [];

        $table = new Table($output);
        $table->setHeaders(['Table', 'Colonne', 'Type', 'AUTO_INCREMENT', 'Max', 'Usage', 'Lignes']);

        foreach ($status as $row) {
            $usage = $row['usage'];

            if ($usage !== null && $usage >= $threshold) {
                $over[] = $row;
            } elseif (!$showAll) {
                continue;
            }

            $usageLabel = $usage === null ? '?' : sprintf('%.2f %%', $usage);
            if ($usage !== null && $usage >= 80) {
                $usageLabel = '<error>' . $usageLabel . '</error>';
            } elseif ($usage !== null && $usage >= $threshold) {
                $usageLabel = '<comment>' . $usageLabel . '</comment>';
            }

            $table->addRow([
                $row['table'],
                $row['column'],
                $row['column_type'],
                number_format($row['auto_increment'], 0, '.', ' '),
                $row['max_value'] === null ? '?' : number_format($row['max_value'], 0, '.', ' '),
                $usageLabel,
                number_format($row['rows'], 0, '.', ' '),
            ]);
        }

        $table->render();

        if ($over === []) {
            $output->writeln(sprintf('<info>Aucune table au-dessus de %.0f %% ✅</info>', $threshold));

            return Command::SUCCESS;
        }

        $output->writeln('');
        $output->writeln(sprintf('<comment>%d table(s) au-dessus de %.0f %%.</comment>', count($over), $threshold));
        $output->writeln('Pour les remettre à plat :');
        foreach ($over as $row) {
            $output->writeln(sprintf('    php bin/console app:db:ids:compact %s', $row['table']));
        }

        return Command::FAILURE;
    }
}
