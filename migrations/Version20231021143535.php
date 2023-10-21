<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231021143535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stop_times ADD id INT AUTO_INCREMENT NOT NULL, CHANGE trip_id trip_id VARCHAR(255) DEFAULT NULL, CHANGE stop_id stop_id VARCHAR(255) DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stop_times MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON stop_times');
        $this->addSql('ALTER TABLE stop_times DROP id, CHANGE trip_id trip_id VARCHAR(255) NOT NULL, CHANGE stop_id stop_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE stop_times ADD PRIMARY KEY (trip_id, stop_id)');
    }
}
