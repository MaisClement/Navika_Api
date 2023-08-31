<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230830204324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trips DROP FOREIGN KEY FK_AA7370DA50266CBB');
        $this->addSql('DROP TABLE shapes');
        $this->addSql('CREATE TABLE shapes (id INT AUTO_INCREMENT NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, shape_id VARCHAR(255) NOT NULL, shape_pt_lat VARCHAR(255) NOT NULL, shape_pt_lon VARCHAR(255) NOT NULL, shape_pt_sequence INT NOT NULL, shape_dist_traveled NUMERIC(15, 0) DEFAULT NULL, INDEX IDX_93DBA512A53A8AA (provider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stop_town (id INT AUTO_INCREMENT NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, town_id VARCHAR(255) DEFAULT NULL, INDEX IDX_A60005BB3902063D (stop_id), INDEX IDX_A60005BB75E23604 (town_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stop_town ADD CONSTRAINT FK_A60005BB3902063D FOREIGN KEY (stop_id) REFERENCES stops (stop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stop_town ADD CONSTRAINT FK_A60005BB75E23604 FOREIGN KEY (town_id) REFERENCES town (town_id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX calendar_service_id ON calendar (service_id)');
        $this->addSql('ALTER TABLE shapes ADD CONSTRAINT FK_93DBA512A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trafic DROP FOREIGN KEY FK_D279A23A53A8AA');
        $this->addSql('DROP INDEX IDX_D279A23A53A8AA ON trafic');
        $this->addSql('ALTER TABLE trafic DROP provider_id');
        $this->addSql('DROP INDEX IDX_AA7370DA50266CBB ON trips');
        $this->addSql('ALTER TABLE trips CHANGE shape_id shape_id VARCHAR(255) NOT NULL, CHANGE direction_id direction_id ENUM("0", "1"), CHANGE wheelchair_accessible wheelchair_accessible ENUM("0", "1", "2"), CHANGE bikes_allowed bikes_allowed ENUM("0", "1", "2")');
        $this->addSql('ALTER TABLE stops CHANGE location_type location_type ENUM("0", "1", "2", "3", "4"), CHANGE wheelchair_boarding wheelchair_boarding ENUM("0", "1", "2")');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stop_town DROP FOREIGN KEY FK_A60005BB3902063D');
        $this->addSql('ALTER TABLE stop_town DROP FOREIGN KEY FK_A60005BB75E23604');
        $this->addSql('DROP TABLE stop_town');
        $this->addSql('DROP INDEX calendar_service_id ON calendar');
        $this->addSql('ALTER TABLE trips CHANGE direction_id direction_id VARCHAR(255) DEFAULT NULL, CHANGE wheelchair_accessible wheelchair_accessible VARCHAR(255) DEFAULT NULL, CHANGE bikes_allowed bikes_allowed VARCHAR(255) DEFAULT NULL, CHANGE shape_id shape_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_AA7370DA50266CBB ON trips (shape_id)');
        $this->addSql('ALTER TABLE shapes DROP FOREIGN KEY FK_93DBA512A53A8AA');
        $this->addSql('ALTER TABLE trafic ADD provider_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE trafic ADD CONSTRAINT FK_D279A23A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D279A23A53A8AA ON trafic (provider_id)');
        $this->addSql('ALTER TABLE stops CHANGE location_type location_type VARCHAR(255) DEFAULT NULL, CHANGE wheelchair_boarding wheelchair_boarding VARCHAR(255) DEFAULT NULL');
    }
}
