<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241005103514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE route_details (id INT AUTO_INCREMENT NOT NULL, route_id VARCHAR(255) NOT NULL, vehicule_name VARCHAR(255) NOT NULL, vehicule_img LONGTEXT DEFAULT NULL, is_air_conditioned TINYINT(1) NOT NULL, has_power_sockets TINYINT(1) NOT NULL, is_bike_accesible TINYINT(1) NOT NULL, is_wheelchair_accesible TINYINT(1) NOT NULL, INDEX IDX_8A75561234ECB4E6 (route_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE messages ADD is_reduced TINYINT(1) NOT NULL, ADD img VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messages DROP is_reduced, DROP img');
        $this->addSql('ALTER TABLE route_details DROP FOREIGN KEY FK_8A75561234ECB4E6');
        $this->addSql('DROP TABLE route_details');
    }
}
