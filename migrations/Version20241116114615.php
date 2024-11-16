<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241116114615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stop_extensions (id INT AUTO_INCREMENT NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, object_id VARCHAR(255) DEFAULT NULL, object_code VARCHAR(255) NOT NULL, INDEX IDX_E3C4DCCEA53A8AA (provider_id), INDEX stop_extensions_object_code (object_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stop_extensions ADD CONSTRAINT FK_E3C4DCCEA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stop_extensions ADD CONSTRAINT FK_E3C4DCCE232D562B FOREIGN KEY (object_id) REFERENCES stops (stop_id) ON DELETE CASCADE');
        
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stop_extensions DROP FOREIGN KEY FK_E3C4DCCEA53A8AA');
        $this->addSql('ALTER TABLE stop_extensions DROP FOREIGN KEY FK_E3C4DCCE232D562B');
        $this->addSql('DROP TABLE stop_extensions');
        
    }
}
