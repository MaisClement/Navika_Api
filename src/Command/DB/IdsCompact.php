<?php

namespace App\Command\DB;

use App\Service\DB;
use App\Service\Logger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Renumérote les clés auto-incrémentées d'une table pour faire redescendre son
 * compteur AUTO_INCREMENT, et peut au passage élargir la colonne en BIGINT.
 *
 * La table est reconstruite dans une table jumelle puis échangée par RENAME,
 * comme le fait app:gtfs:update : la table d'origine reste servie jusqu'à
 * l'échange, qui est atomique.
 *
 * La renumérotation change les id : la commande refuse de traiter une table
 * dont la colonne auto-incrémentée est référencée par une clé étrangère.
 */
class IdsCompact extends Command
{
    private EntityManagerInterface $entityManager;
    private DB $DB;
    private Logger $logger;

    public function __construct(EntityManagerInterface $entityManager, DB $DB, Logger $logger)
    {
        $this->entityManager = $entityManager;
        $this->DB = $DB;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:db:ids:compact')
            ->setDescription('Renumérote les id d\'une table pour faire redescendre son AUTO_INCREMENT')
            ->addArgument('tables', InputArgument::IS_ARRAY, 'Tables à compacter (vide = toutes celles au-dessus du seuil)')
            ->addOption('threshold', 't', InputOption::VALUE_REQUIRED, 'Seuil de sélection automatique en % du type de colonne', '50')
            ->addOption('bigint', 'b', InputOption::VALUE_NONE, 'Élargit aussi la colonne en BIGINT UNSIGNED')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Affiche ce qui serait fait sans rien modifier')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ne demande pas confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = $this->entityManager->getConnection();

        $dryRun = (bool) $input->getOption('dry-run');
        $toBigint = (bool) $input->getOption('bigint');
        $tables = $input->getArgument('tables');

        if ($tables === []) {
            $threshold = (float) $input->getOption('threshold');
            foreach ($this->DB->getAutoIncrementStatus($db) as $status) {
                if ($status['usage'] !== null && $status['usage'] >= $threshold) {
                    $tables[] = $status['table'];
                }
            }

            if ($tables === []) {
                $output->writeln(sprintf('<info>Aucune table au-dessus de %.0f %%, rien à faire ✅</info>', $threshold));

                return Command::SUCCESS;
            }

            $output->writeln(sprintf('Tables au-dessus de %.0f %% : %s', $threshold, implode(', ', $tables)));
        }

        if (!$dryRun && $input->getOption('force') !== true) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                sprintf('Renuméroter %s ? Les id actuels seront perdus. [y/N] ', implode(', ', $tables)),
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Annulé.');

                return Command::SUCCESS;
            }
        }

        $failed = 0;
        foreach ($tables as $table) {
            if (!$this->compact($db, (string) $table, $toBigint, $dryRun, $output)) {
                $failed++;
            }
            $output->writeln('');
        }

        if ($failed > 0) {
            $output->writeln(sprintf('<error>%d table(s) non traitée(s)</error>', $failed));

            return Command::FAILURE;
        }

        $output->writeln('<info>Terminé ✅</info>');

        return Command::SUCCESS;
    }

    /**
     * Élargit une colonne auto-incrémentée en BIGINT UNSIGNED sans renuméroter,
     * en élargissant du même coup les colonnes qui la référencent (une clé
     * étrangère impose des types compatibles des deux côtés).
     *
     * @param array<int, array{table: string, column: string, constraint: string}> $referencing
     */
    private function widen($db, string $table, string $column, array $referencing, bool $dryRun, OutputInterface $output): bool
    {
        if ($dryRun) {
            $output->writeln('    [dry-run] élargirait ' . $table . '.' . $column . ' et les colonnes référençantes en BIGINT UNSIGNED');

            return true;
        }

        $incoming = $this->DB->getIncomingForeignKeyDefinitions($db, $table);

        try {
            $db->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

            foreach ($incoming as $definition) {
                $this->DB->dropForeignKey($db, $definition['table'], $definition['name']);
            }

            $output->writeln('    > Élargissement de ' . $table . '.' . $column . '...');
            $db->executeStatement("ALTER TABLE `$table` MODIFY `$column` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");

            foreach ($referencing as $reference) {
                $output->writeln('    > Élargissement de ' . $reference['table'] . '.' . $reference['column'] . '...');

                $nullable = $db->executeQuery(
                    "SELECT IS_NULLABLE
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                    [$reference['table'], $reference['column']]
                )->fetchOne();

                $null = $nullable === 'NO' ? 'NOT NULL' : 'DEFAULT NULL';

                $db->executeStatement(sprintf(
                    'ALTER TABLE `%s` MODIFY `%s` BIGINT UNSIGNED %s',
                    $reference['table'],
                    $reference['column'],
                    $null
                ));
            }

            foreach ($incoming as $definition) {
                $this->DB->addForeignKey($db, $definition);
            }

            $db->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

            $output->writeln('    <info>OK ✅ colonne élargie en BIGINT UNSIGNED</info>');
            $this->logger->log(['message' => "[app:db:ids:compact] $table.$column élargie en BIGINT UNSIGNED"], 'INFO');

            return true;

        } catch (\Throwable $e) {
            $db->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

            $output->writeln('    <error>' . $e->getMessage() . '</error>');
            $this->logger->error($e, 'ERROR', "[app:db:ids:compact][$table] ");

            return false;
        }
    }

    private function compact($db, string $table, bool $toBigint, bool $dryRun, OutputInterface $output): bool
    {
        $output->writeln("<comment>$table</comment>");

        if (!$this->DB->tableExists($db, $table)) {
            $output->writeln('    <error>Table inconnue</error>');

            return false;
        }

        $column = $this->DB->getAutoIncrementColumn($db, $table);
        if ($column === null) {
            $output->writeln('    i Pas de colonne AUTO_INCREMENT, ignorée');

            return true;
        }

        // Renuméroter réécrit les id : toute clé étrangère qui les référence
        // deviendrait incohérente. Dans ce cas on ne peut qu'élargir la colonne,
        // ce qui repousse la limite sans toucher aux valeurs.
        $referencing = $this->DB->getIncomingForeignKeys($db, $table, $column);
        if ($referencing !== []) {
            $referenced = implode(', ', array_map(
                static fn(array $r): string => $r['table'] . '.' . $r['column'],
                $referencing
            ));

            if (!$toBigint) {
                $output->writeln(sprintf(
                    '    <error>%s.%s est référencée par %s — renumérotation impossible, relancer avec --bigint pour élargir la colonne</error>',
                    $table,
                    $column,
                    $referenced
                ));

                return false;
            }

            $output->writeln(sprintf('    i Référencée par %s : élargissement seul, id conservés', $referenced));

            return $this->widen($db, $table, $column, $referencing, $dryRun, $output);
        }

        $rows = $this->DB->countRows($db, $table);
        $current = 0;
        foreach ($this->DB->getAutoIncrementStatus($db) as $status) {
            if ($status['table'] === $table) {
                $current = $status['auto_increment'];
                $output->writeln(sprintf(
                    '    AUTO_INCREMENT %s (%s) pour %s lignes',
                    number_format($status['auto_increment'], 0, '.', ' '),
                    $status['column_type'],
                    number_format($rows, 0, '.', ' ')
                ));
                break;
            }
        }

        if ($dryRun) {
            $output->writeln(sprintf(
                '    [dry-run] reconstruirait la table, AUTO_INCREMENT retomberait à l\'ordre de %s%s',
                number_format($rows + 1, 0, '.', ' '),
                $toBigint ? ', colonne élargie en BIGINT UNSIGNED' : ''
            ));

            return true;
        }

        $newTable = 'compact_' . $table;
        $oldTable = 'old_compact_' . $table;

        // On relève les clés étrangères avant d'y toucher : celles portées par la
        // table (perdues avec elle) et celles qui la référencent (que le RENAME
        // ferait suivre vers l'ancienne table).
        $outgoing = $this->DB->getForeignKeyDefinitions($db, $table);
        $incoming = $this->DB->getIncomingForeignKeyDefinitions($db, $table);

        try {
            $this->DB->dropTable($db, $newTable);
            $this->DB->dropTable($db, $oldTable);

            $output->writeln('    > Création de la table de reconstruction...');
            $db->executeStatement("CREATE TABLE `$newTable` LIKE `$table`");

            if ($toBigint) {
                $output->writeln('    > Élargissement de la colonne en BIGINT UNSIGNED...');
                $db->executeStatement(
                    "ALTER TABLE `$newTable` MODIFY `$column` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT"
                );
            }

            $db->executeStatement("ALTER TABLE `$newTable` AUTO_INCREMENT = 1");

            // La copie omet la colonne auto-incrémentée : la nouvelle table
            // réattribue les id à partir de 1, dans l'ordre des id actuels.
            $columns = $this->DB->getCopyColumns($db, $table, $newTable);
            $list = '`' . implode('`, `', $columns) . '`';

            $output->writeln(sprintf('    > Copie de %s lignes...', number_format($rows, 0, '.', ' ')));
            $db->executeStatement(
                "INSERT INTO `$newTable` ($list) SELECT $list FROM `$table` ORDER BY `$column`"
            );

            $copied = $this->DB->countRows($db, $newTable);
            if ($copied !== $rows) {
                throw new \RuntimeException("Copie incomplète : $copied lignes copiées sur $rows attendues");
            }

            $output->writeln('    > Échange des tables...');
            $db->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

            foreach ($incoming as $definition) {
                $this->DB->dropForeignKey($db, $definition['table'], $definition['name']);
            }

            $db->executeStatement("RENAME TABLE `$table` TO `$oldTable`, `$newTable` TO `$table`");

            // L'ancienne table emporte ses propres clés étrangères, il faut la
            // supprimer avant de recréer les mêmes noms sur la nouvelle.
            $this->DB->dropTable($db, $oldTable);

            $output->writeln('    > Recréation des clés étrangères...');
            foreach (array_merge($outgoing, $incoming) as $definition) {
                $this->DB->addForeignKey($db, $definition);
            }

            $db->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

            $new = 0;
            foreach ($this->DB->getAutoIncrementStatus($db, true) as $status) {
                if ($status['table'] === $table) {
                    $new = $status['auto_increment'];
                    break;
                }
            }

            $output->writeln(sprintf(
                '    <info>OK ✅ AUTO_INCREMENT %s → %s</info>',
                number_format($current, 0, '.', ' '),
                number_format($new, 0, '.', ' ')
            ));

            $this->logger->log(['message' => sprintf(
                '[app:db:ids:compact] %s.%s renumérotée : AUTO_INCREMENT %d -> %d (%d lignes)',
                $table,
                $column,
                $current,
                $new,
                $rows
            )], 'INFO');

            return true;

        } catch (\Throwable $e) {
            $db->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

            $output->writeln('    <error>' . $e->getMessage() . '</error>');
            $this->logger->error($e, 'ERROR', "[app:db:ids:compact][$table] ");

            // La table d'origine n'a pu disparaître qu'après un RENAME réussi.
            // Si elle est toujours là, l'échange n'a pas eu lieu : on nettoie.
            if ($this->DB->tableExists($db, $table) && $this->DB->tableExists($db, $oldTable)) {
                $output->writeln('    <error>Échange interrompu : ' . $oldTable . ' conservée pour inspection</error>');
            } else {
                $this->DB->dropTable($db, $newTable);
            }

            return false;
        }
    }
}
