<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240612174903 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE route_details (id INT AUTO_INCREMENT NOT NULL, route_id VARCHAR(255) NOT NULL, vehicule_name VARCHAR(255) NOT NULL, vehicule_img LONGTEXT DEFAULT NULL, is_air_conditioned TINYINT(1) NOT NULL, has_power_sockets TINYINT(1) NOT NULL, is_bike_accesible TINYINT(1) NOT NULL, is_wheelchair_accesible TINYINT(1) NOT NULL, INDEX IDX_8A75561234ECB4E6 (route_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE route_details ADD CONSTRAINT FK_8A75561234ECB4E6 FOREIGN KEY (route_id) REFERENCES routes (route_id)');
        $this->addSql('ALTER TABLE attributions CHANGE is_producer is_producer ENUM("0", "1"), CHANGE is_operator is_operator ENUM("0", "1"), CHANGE is_authority is_authority ENUM("0", "1")');
        $this->addSql('ALTER TABLE calendar CHANGE monday monday ENUM("0", "1"), CHANGE tuesday tuesday ENUM("0", "1"), CHANGE wednesday wednesday ENUM("0", "1"), CHANGE thursday thursday ENUM("0", "1"), CHANGE friday friday ENUM("0", "1"), CHANGE saturday saturday ENUM("0", "1"), CHANGE sunday sunday ENUM("0", "1")');
        $this->addSql('ALTER TABLE calendar_dates CHANGE exception_type exception_type ENUM("0", "1", "2")');
        $this->addSql('ALTER TABLE fare_attributes CHANGE payment_method payment_method ENUM("0", "1"), CHANGE transfers transfers ENUM("0", "1", "2")');
        $this->addSql('ALTER TABLE frequencies CHANGE exact_times exact_times ENUM("0", "1")');
        $this->addSql('ALTER TABLE pathways CHANGE pathway_mode pathway_mode ENUM("0", "1", "2", "3", "4", "5", "6", "7"), CHANGE is_bidirectional is_bidirectional ENUM("0", "1")');
        $this->addSql('ALTER TABLE provider CHANGE flag flag ENUM("0", "1", "2")');
        $this->addSql('ALTER TABLE route_sub CHANGE monday monday ENUM("0", "1"), CHANGE tuesday tuesday ENUM("0", "1"), CHANGE wednesday wednesday ENUM("0", "1"), CHANGE thursday thursday ENUM("0", "1"), CHANGE friday friday ENUM("0", "1"), CHANGE saturday saturday ENUM("0", "1"), CHANGE sunday sunday ENUM("0", "1"), CHANGE type type ENUM("alert", "all")');
        $this->addSql('ALTER TABLE routes CHANGE continuous_pickup continuous_pickup ENUM("0", "1", "2", "3"), CHANGE continuous_drop_off continuous_drop_off ENUM("0", "1", "2", "3")');
        $this->addSql('DROP INDEX stop_route_query ON stop_route');
        $this->addSql('CREATE INDEX stop_route_query ON stop_route (stop_query_name)');
        $this->addSql('ALTER TABLE stop_times CHANGE pickup_type pickup_type ENUM("0", "1", "2", "3"), CHANGE drop_off_type drop_off_type ENUM("0", "1", "2", "3"), CHANGE continuous_pickup continuous_pickup ENUM("0", "1", "2", "3"), CHANGE continuous_drop_off continuous_drop_off ENUM("0", "1", "2", "3"), CHANGE timepoint timepoint ENUM("0", "1")');
        $this->addSql('ALTER TABLE stops CHANGE location_type location_type ENUM("0", "1", "2", "3", "4"), CHANGE wheelchair_boarding wheelchair_boarding ENUM("0", "1", "2")');
        $this->addSql('ALTER TABLE timetables CHANGE type type ENUM("timetables", "map")');
        $this->addSql('DROP INDEX town_polygon ON town');
        $this->addSql('CREATE INDEX town_polygon ON town (town_polygon)');
        $this->addSql('ALTER TABLE transfers CHANGE transfer_type transfer_type ENUM("0", "1", "2", "3", "4", "5")');
        $this->addSql('DROP INDEX trips_trip_short_name ON trips');
        $this->addSql('ALTER TABLE trips CHANGE direction_id direction_id ENUM("0", "1"), CHANGE wheelchair_accessible wheelchair_accessible ENUM("0", "1", "2"), CHANGE bikes_allowed bikes_allowed ENUM("0", "1", "2")');
        $this->addSql('CREATE INDEX trips_trip_short_name ON trips (trip_short_name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE route_details DROP FOREIGN KEY FK_8A75561234ECB4E6');
        $this->addSql('DROP TABLE route_details');
        $this->addSql('ALTER TABLE calendar_dates CHANGE exception_type exception_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE routes CHANGE continuous_pickup continuous_pickup VARCHAR(255) DEFAULT NULL, CHANGE continuous_drop_off continuous_drop_off VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE calendar CHANGE monday monday VARCHAR(255) DEFAULT NULL, CHANGE tuesday tuesday VARCHAR(255) DEFAULT NULL, CHANGE wednesday wednesday VARCHAR(255) DEFAULT NULL, CHANGE thursday thursday VARCHAR(255) DEFAULT NULL, CHANGE friday friday VARCHAR(255) DEFAULT NULL, CHANGE saturday saturday VARCHAR(255) DEFAULT NULL, CHANGE sunday sunday VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE route_sub CHANGE type type VARCHAR(255) DEFAULT NULL, CHANGE monday monday VARCHAR(255) DEFAULT NULL, CHANGE tuesday tuesday VARCHAR(255) DEFAULT NULL, CHANGE wednesday wednesday VARCHAR(255) DEFAULT NULL, CHANGE thursday thursday VARCHAR(255) DEFAULT NULL, CHANGE friday friday VARCHAR(255) DEFAULT NULL, CHANGE saturday saturday VARCHAR(255) DEFAULT NULL, CHANGE sunday sunday VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE stop_times CHANGE pickup_type pickup_type VARCHAR(255) DEFAULT NULL, CHANGE drop_off_type drop_off_type VARCHAR(255) DEFAULT NULL, CHANGE continuous_pickup continuous_pickup VARCHAR(255) DEFAULT NULL, CHANGE continuous_drop_off continuous_drop_off VARCHAR(255) DEFAULT NULL, CHANGE timepoint timepoint VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX trips_trip_short_name ON trips');
        $this->addSql('ALTER TABLE trips CHANGE direction_id direction_id VARCHAR(255) DEFAULT NULL, CHANGE wheelchair_accessible wheelchair_accessible VARCHAR(255) DEFAULT NULL, CHANGE bikes_allowed bikes_allowed VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX trips_trip_short_name ON trips (trip_short_name(768))');
        $this->addSql('ALTER TABLE provider CHANGE flag flag VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE stops CHANGE location_type location_type VARCHAR(255) DEFAULT NULL, CHANGE wheelchair_boarding wheelchair_boarding VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE transfers CHANGE transfer_type transfer_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX stop_route_query ON stop_route');
        $this->addSql('CREATE INDEX stop_route_query ON stop_route (stop_query_name(768))');
        $this->addSql('ALTER TABLE frequencies CHANGE exact_times exact_times VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE timetables CHANGE type type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pathways CHANGE pathway_mode pathway_mode VARCHAR(255) DEFAULT NULL, CHANGE is_bidirectional is_bidirectional VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE fare_attributes CHANGE payment_method payment_method VARCHAR(255) DEFAULT NULL, CHANGE transfers transfers VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX town_polygon ON town');
        $this->addSql('CREATE INDEX town_polygon ON town (town_polygon(3072))');
        $this->addSql('ALTER TABLE attributions CHANGE is_producer is_producer VARCHAR(255) DEFAULT NULL, CHANGE is_operator is_operator VARCHAR(255) DEFAULT NULL, CHANGE is_authority is_authority VARCHAR(255) DEFAULT NULL');
    }
}
