<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240316174707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE trafic_application_periods (id INT AUTO_INCREMENT NOT NULL, report_id_id INT NOT NULL, begin DATETIME NOT NULL, end DATETIME NOT NULL, INDEX IDX_1F1040C85558992E (report_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE trafic_application_periods ADD CONSTRAINT FK_1F1040C85558992E FOREIGN KEY (report_id_id) REFERENCES trafic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trafic_links DROP FOREIGN KEY FK_74090179DACF3E36');
        $this->addSql('DROP INDEX IDX_74090179DACF3E36 ON trafic_links');
        $this->addSql('ALTER TABLE trafic_links CHANGE trafic_id_id report_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE trafic_links ADD CONSTRAINT FK_740901795558992E FOREIGN KEY (report_id_id) REFERENCES trafic (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_740901795558992E ON trafic_links (report_id_id)');
        }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trafic_application_periods DROP FOREIGN KEY FK_1F1040C85558992E');
        $this->addSql('DROP TABLE trafic_application_periods');
        $this->addSql('ALTER TABLE trafic_links DROP FOREIGN KEY FK_740901795558992E');
        $this->addSql('DROP INDEX IDX_740901795558992E ON trafic_links');
        $this->addSql('ALTER TABLE trafic_links CHANGE report_id_id trafic_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE trafic_links ADD CONSTRAINT FK_74090179DACF3E36 FOREIGN KEY (trafic_id_id) REFERENCES trafic (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_74090179DACF3E36 ON trafic_links (trafic_id_id)');
        }
}
