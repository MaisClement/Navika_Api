<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Étend motis_instances pour porter l'état du déploiement blue-green.
 *
 * `active` désigne l'instance vers laquelle l'API route ; `healthy` /
 * `checked_at` sont tenus à jour par app:motis:health ; `data_path` /
 * `data_hash` disent quel jeu de données une instance sert et s'il correspond
 * encore aux GTFS courants.
 */
final class Version20260905130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l\'etat blue-green sur motis_instances';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE motis_instances
            ADD active TINYINT(1) DEFAULT 0 NOT NULL,
            ADD healthy TINYINT(1) DEFAULT 0 NOT NULL,
            ADD checked_at DATETIME DEFAULT NULL,
            ADD started_at DATETIME DEFAULT NULL,
            ADD data_path VARCHAR(255) DEFAULT NULL,
            ADD data_hash VARCHAR(255) DEFAULT NULL,
            ADD last_error LONGTEXT DEFAULT NULL');

        // Les instances existantes n'ont pas d'état connu : app:motis:health le
        // rétablira à sa première exécution.
        $this->addSql("UPDATE motis_instances SET state = 'unknown', healthy = 0, active = 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE motis_instances
            DROP active,
            DROP healthy,
            DROP checked_at,
            DROP started_at,
            DROP data_path,
            DROP data_hash,
            DROP last_error');
    }
}
