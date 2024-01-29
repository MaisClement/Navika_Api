<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240129180211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE trafic_links (id INT AUTO_INCREMENT NOT NULL, trafic_id_id INT NOT NULL, link VARCHAR(255) NOT NULL, INDEX IDX_74090179DACF3E36 (trafic_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE trafic_links ADD CONSTRAINT FK_74090179DACF3E36 FOREIGN KEY (trafic_id_id) REFERENCES trafic (id)');
        $this->addSql('ALTER TABLE stop_town DROP FOREIGN KEY FK_A60005BB3902063D');
        $this->addSql('ALTER TABLE stop_town DROP FOREIGN KEY FK_A60005BB75E23604');
        $this->addSql('DROP TABLE stop_town');
        $this->addSql('ALTER TABLE provider ADD gtfs_url VARCHAR(255) DEFAULT NULL, ADD gbfs_url VARCHAR(255) DEFAULT NULL, ADD gtfsrt_service_alerts VARCHAR(255) DEFAULT NULL, ADD gtfsrt_vehicle_positions VARCHAR(255) DEFAULT NULL, ADD gtfsrt_trip_updates VARCHAR(255) DEFAULT NULL, ADD siri VARCHAR(255) DEFAULT NULL, CHANGE flag flag ENUM("0", "1", "2")');
        $this->addSql('ALTER TABLE subscribers CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stop_town (id INT AUTO_INCREMENT NOT NULL, stop_id VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, town_id VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_A60005BB3902063D (stop_id), INDEX IDX_A60005BB75E23604 (town_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE stop_town ADD CONSTRAINT FK_A60005BB3902063D FOREIGN KEY (stop_id) REFERENCES stops (stop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stop_town ADD CONSTRAINT FK_A60005BB75E23604 FOREIGN KEY (town_id) REFERENCES town (town_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trafic_links DROP FOREIGN KEY FK_74090179DACF3E36');
        $this->addSql('DROP TABLE trafic_links');
        $this->addSql('ALTER TABLE subscribers CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE provider DROP gtfs_url, DROP gbfs_url, DROP gtfsrt_service_alerts, DROP gtfsrt_vehicle_positions, DROP gtfsrt_trip_updates, DROP siri, CHANGE flag flag VARCHAR(255) DEFAULT NULL');
        }
}
