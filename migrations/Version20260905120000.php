<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Passe en BIGINT UNSIGNED les clés auto-incrémentées des tables à forte
 * rotation, dont les compteurs approchaient la capacité d'un INT signé
 * (shapes était à 53 % de 2 147 483 647 pour 3,5 M de lignes).
 *
 * Cette migration ne fait qu'élargir les colonnes, elle ne renumérote rien :
 * pour faire redescendre un compteur, utiliser app:db:ids:compact.
 */
final class Version20260905120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Elargit en BIGINT UNSIGNED les cles auto-incrementees des tables a forte rotation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        // Tables reconstruites par app:gtfs:update : aucune clé étrangère ne
        // référence leur id, l'ALTER se suffit à lui-même.
        $this->addSql('ALTER TABLE stop_times MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE shapes MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE calendar_dates MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        // trafic.id est référencée : les colonnes des deux côtés d'une clé
        // étrangère doivent avoir le même type, on les élargit ensemble.
        $this->addSql('ALTER TABLE trafic_links DROP FOREIGN KEY FK_740901795558992E');
        $this->addSql('ALTER TABLE trafic_application_periods DROP FOREIGN KEY FK_1F1040C85558992E');

        $this->addSql('ALTER TABLE trafic MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE trafic_links MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE trafic_application_periods MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE trafic_links MODIFY report_id_id BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE trafic_application_periods MODIFY report_id_id BIGINT UNSIGNED NOT NULL');

        $this->addSql('ALTER TABLE trafic_links ADD CONSTRAINT FK_740901795558992E FOREIGN KEY (report_id_id) REFERENCES trafic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trafic_application_periods ADD CONSTRAINT FK_1F1040C85558992E FOREIGN KEY (report_id_id) REFERENCES trafic (id) ON DELETE CASCADE');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        $this->addSql('ALTER TABLE stop_times MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE shapes MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE calendar_dates MODIFY id INT AUTO_INCREMENT NOT NULL');

        $this->addSql('ALTER TABLE trafic_links DROP FOREIGN KEY FK_740901795558992E');
        $this->addSql('ALTER TABLE trafic_application_periods DROP FOREIGN KEY FK_1F1040C85558992E');

        $this->addSql('ALTER TABLE trafic MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE trafic_links MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE trafic_application_periods MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE trafic_links MODIFY report_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE trafic_application_periods MODIFY report_id_id INT NOT NULL');

        $this->addSql('ALTER TABLE trafic_links ADD CONSTRAINT FK_740901795558992E FOREIGN KEY (report_id_id) REFERENCES trafic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trafic_application_periods ADD CONSTRAINT FK_1F1040C85558992E FOREIGN KEY (report_id_id) REFERENCES trafic (id) ON DELETE CASCADE');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
