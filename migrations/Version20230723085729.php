<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230723085729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agency (agency_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, agency_name VARCHAR(255) NOT NULL, agency_url VARCHAR(255) NOT NULL, agency_timezone VARCHAR(255) NOT NULL, agency_lang VARCHAR(255) DEFAULT NULL, agency_phone VARCHAR(255) DEFAULT NULL, agency_fare_url VARCHAR(255) DEFAULT NULL, agency_email VARCHAR(255) DEFAULT NULL, INDEX IDX_70C0C6E6A53A8AA (provider_id), PRIMARY KEY(agency_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE attributions (attribution_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, agency_id VARCHAR(255) DEFAULT NULL, route_id VARCHAR(255) DEFAULT NULL, trip_id VARCHAR(255) DEFAULT NULL, organization_name VARCHAR(255) NOT NULL, is_producer ENUM(\'0\', \'1\'), is_operator ENUM(\'0\', \'1\'), is_authority ENUM(\'0\', \'1\'), attribution_url VARCHAR(255) DEFAULT NULL, attribution_email VARCHAR(255) DEFAULT NULL, attribution_phone VARCHAR(255) DEFAULT NULL, INDEX IDX_14C967D2A53A8AA (provider_id), PRIMARY KEY(attribution_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE calendar (service_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, monday ENUM(\'0\', \'1\'), tuesday ENUM(\'0\', \'1\'), wednesday ENUM(\'0\', \'1\'), thursday ENUM(\'0\', \'1\'), friday ENUM(\'0\', \'1\'), saturday ENUM(\'0\', \'1\'), sunday ENUM(\'0\', \'1\'), start_date DATE NOT NULL, end_date DATE NOT NULL, INDEX IDX_6EA9A146A53A8AA (provider_id), PRIMARY KEY(service_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE calendar_dates (id INT AUTO_INCREMENT NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, service_id VARCHAR(255) DEFAULT NULL, date DATE NOT NULL, exception_type ENUM(\'0\', \'1\', \'2\'), INDEX IDX_C720CA70A53A8AA (provider_id), INDEX calendar_dates_service_id (service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fare_attributes (fare_id VARCHAR(255) NOT NULL, agency_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, price NUMERIC(15, 0) NOT NULL, currency_type VARCHAR(255) NOT NULL, payment_method ENUM(\'0\', \'1\'), transfers ENUM(\'0\', \'1\', \'2\'), transfer_duration INT DEFAULT NULL, INDEX IDX_F5CBF19AA53A8AA (provider_id), INDEX IDX_F5CBF19AA048D2E2 (fare_id), INDEX IDX_F5CBF19ACDEADB2A (agency_id), PRIMARY KEY(fare_id, agency_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fare_rules (fare_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, route_id VARCHAR(255) DEFAULT NULL, origin_id VARCHAR(255) DEFAULT NULL, destination_id VARCHAR(255) DEFAULT NULL, contains_id VARCHAR(255) DEFAULT NULL, INDEX IDX_F1F0A80AA53A8AA (provider_id), INDEX IDX_F1F0A80A34ECB4E6 (route_id), INDEX IDX_F1F0A80A56A273CC (origin_id), INDEX IDX_F1F0A80A816C6140 (destination_id), PRIMARY KEY(fare_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feed_info (id INT AUTO_INCREMENT NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, feed_publisher_name VARCHAR(255) NOT NULL, feed_publisher_url VARCHAR(255) NOT NULL, feed_lang VARCHAR(255) NOT NULL, default_lang VARCHAR(255) DEFAULT NULL, feed_start_date DATE DEFAULT NULL, feed_end_date DATE DEFAULT NULL, feed_version VARCHAR(255) DEFAULT NULL, feed_contact_email VARCHAR(255) DEFAULT NULL, feed_contact_url VARCHAR(255) DEFAULT NULL, INDEX IDX_4B1EDA00A53A8AA (provider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE frequencies (trip_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, headway_secs INT NOT NULL, exact_times ENUM(\'0\', \'1\'), INDEX IDX_282C52B8A53A8AA (provider_id), PRIMARY KEY(trip_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE levels (level_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, level_index VARCHAR(8) NOT NULL, level_name VARCHAR(255) DEFAULT NULL, INDEX IDX_9F2A6419A53A8AA (provider_id), PRIMARY KEY(level_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messages (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, severity INT NOT NULL, effect VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL, title VARCHAR(255) NOT NULL, text VARCHAR(255) NOT NULL, button VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pathways (pathway_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, from_stop_id VARCHAR(255) DEFAULT NULL, to_stop_id VARCHAR(255) DEFAULT NULL, pathway_mode ENUM(\'0\', \'1\', \'2\', \'3\', \'4\', \'5\', \'6\', \'7\'), is_bidirectional ENUM(\'0\', \'1\'), length NUMERIC(15, 0) DEFAULT NULL, traversal_time VARCHAR(8) DEFAULT NULL, stair_count VARCHAR(8) DEFAULT NULL, max_slope VARCHAR(8) DEFAULT NULL, min_width VARCHAR(8) DEFAULT NULL, signposted_as VARCHAR(255) DEFAULT NULL, reversed_signposted_as VARCHAR(255) DEFAULT NULL, INDEX IDX_554EA1F8A53A8AA (provider_id), INDEX IDX_554EA1F8BF5CE592 (from_stop_id), INDEX IDX_554EA1F8B79A3BC8 (to_stop_id), PRIMARY KEY(pathway_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE provider (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, area VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, updated_at DATETIME DEFAULT NULL, flag ENUM(\'0\', \'1\', \'2\'), type VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE routes (route_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, agency_id VARCHAR(255) NOT NULL, route_short_name LONGTEXT DEFAULT NULL, route_long_name LONGTEXT DEFAULT NULL, route_desc LONGTEXT DEFAULT NULL, route_type VARCHAR(255) DEFAULT NULL, route_url LONGTEXT DEFAULT NULL, route_color VARCHAR(8) DEFAULT NULL, route_text_color VARCHAR(8) DEFAULT NULL, route_sort_order INT DEFAULT NULL, continuous_pickup ENUM(\'0\', \'1\', \'2\', \'3\'), continuous_drop_off ENUM(\'0\', \'1\', \'2\', \'3\'), INDEX IDX_32D5C2B3A53A8AA (provider_id), INDEX IDX_32D5C2B3CDEADB2A (agency_id), PRIMARY KEY(route_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shapes (shape_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, shape_pt_lat VARCHAR(255) NOT NULL, shape_pt_lon VARCHAR(255) NOT NULL, shape_pt_sequence INT NOT NULL, shape_dist_traveled NUMERIC(15, 0) DEFAULT NULL, INDEX IDX_93DBA512A53A8AA (provider_id), PRIMARY KEY(shape_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stations (station_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, station_name VARCHAR(255) NOT NULL, station_lat VARCHAR(255) NOT NULL, station_lon VARCHAR(255) NOT NULL, station_capacity INT DEFAULT NULL, INDEX IDX_A7F775E9A53A8AA (provider_id), PRIMARY KEY(station_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stop_route (route_key VARCHAR(255) NOT NULL, route_id VARCHAR(255) NOT NULL, town_id VARCHAR(255) DEFAULT NULL, stop_id VARCHAR(255) NOT NULL, route_short_name LONGTEXT DEFAULT NULL, route_long_name LONGTEXT DEFAULT NULL, route_type VARCHAR(255) NOT NULL, route_color VARCHAR(8) DEFAULT NULL, route_text_color VARCHAR(8) DEFAULT NULL, stop_name LONGTEXT NOT NULL, stop_query_name LONGTEXT NOT NULL, stop_lat VARCHAR(255) DEFAULT NULL, stop_lon VARCHAR(255) DEFAULT NULL, town_name VARCHAR(255) DEFAULT NULL, town_query_name VARCHAR(255) DEFAULT NULL, zip_code VARCHAR(255) DEFAULT NULL, INDEX IDX_8F26CB4E75E23604 (town_id), INDEX stop_route_stop_id (stop_id), INDEX stop_route_query (stop_query_name), INDEX stop_route_query_town (town_query_name), INDEX stop_route_route_id (route_id), PRIMARY KEY(route_key)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stop_times (trip_id VARCHAR(255) NOT NULL, stop_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, arrival_time TIME DEFAULT NULL, departure_time TIME DEFAULT NULL, stop_sequence INT NOT NULL, stop_headsign VARCHAR(255) DEFAULT NULL, local_zone_id VARCHAR(255) DEFAULT NULL, pickup_type ENUM(\'0\', \'1\', \'2\', \'3\'), drop_off_type ENUM(\'0\', \'1\', \'2\', \'3\'), continuous_pickup ENUM(\'0\', \'1\', \'2\', \'3\'), continuous_drop_off ENUM(\'0\', \'1\', \'2\', \'3\'), shape_dist_traveled NUMERIC(15, 0) DEFAULT NULL, timepoint ENUM(\'0\', \'1\'), INDEX IDX_903505BBA53A8AA (provider_id), INDEX stop_times_trip_id (trip_id), INDEX stop_times_stop_id (stop_id), PRIMARY KEY(trip_id, stop_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stops (stop_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, level_id VARCHAR(255) DEFAULT NULL, stop_code VARCHAR(255) DEFAULT NULL, stop_name VARCHAR(255) NOT NULL, stop_desc VARCHAR(255) DEFAULT NULL, stop_lat VARCHAR(255) NOT NULL, stop_lon VARCHAR(255) NOT NULL, zone_id VARCHAR(255) DEFAULT NULL, stop_url VARCHAR(255) DEFAULT NULL, location_type ENUM(\'0\', \'1\', \'2\', \'3\', \'4\'), vehicle_type VARCHAR(255) DEFAULT NULL, stop_timezone VARCHAR(255) DEFAULT NULL, wheelchair_boarding ENUM(\'0\', \'1\', \'2\'), platform_code VARCHAR(255) DEFAULT NULL, parent_station VARCHAR(255) DEFAULT NULL, INDEX IDX_39B58FA4A53A8AA (provider_id), INDEX IDX_39B58FA45FB14BA7 (level_id), PRIMARY KEY(stop_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE temp_stop_route (route_key VARCHAR(255) NOT NULL, route_id VARCHAR(255) NOT NULL, route_short_name LONGTEXT DEFAULT NULL, route_long_name LONGTEXT DEFAULT NULL, route_type VARCHAR(255) NOT NULL, route_color VARCHAR(8) DEFAULT NULL, route_text_color VARCHAR(8) DEFAULT NULL, stop_id VARCHAR(255) NOT NULL, stop_name LONGTEXT NOT NULL, stop_query_name LONGTEXT NOT NULL, stop_lat VARCHAR(255) NOT NULL, stop_lon VARCHAR(255) NOT NULL, town_id VARCHAR(255) DEFAULT NULL, town_name VARCHAR(255) DEFAULT NULL, town_query_name VARCHAR(255) DEFAULT NULL, zip_code VARCHAR(255) DEFAULT NULL, PRIMARY KEY(route_key)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE town (town_id VARCHAR(255) NOT NULL, town_name VARCHAR(255) NOT NULL, town_polygon POLYGON DEFAULT NULL COMMENT \'(DC2Type:polygon)\', zip_code VARCHAR(255) NOT NULL, INDEX town_polygon (town_polygon), PRIMARY KEY(town_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trafic (id INT AUTO_INCREMENT NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, route_id VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, cause VARCHAR(255) DEFAULT NULL, category VARCHAR(255) DEFAULT NULL, severity INT NOT NULL, effect VARCHAR(255) NOT NULL, updated_at DATETIME NOT NULL, title LONGTEXT DEFAULT NULL, text LONGTEXT DEFAULT NULL, INDEX IDX_D279A23A53A8AA (provider_id), INDEX IDX_D279A2334ECB4E6 (route_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transfers (from_stop_id VARCHAR(255) NOT NULL, to_stop_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, transfer_type ENUM(\'0\', \'1\', \'2\', \'3\', \'4\', \'5\'), min_transfer_time INT DEFAULT NULL, INDEX IDX_802A3918A53A8AA (provider_id), INDEX IDX_802A3918BF5CE592 (from_stop_id), INDEX IDX_802A3918B79A3BC8 (to_stop_id), PRIMARY KEY(from_stop_id, to_stop_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE translations (id INT AUTO_INCREMENT NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, table_name VARCHAR(255) NOT NULL, field_name VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, translation VARCHAR(255) NOT NULL, record_id VARCHAR(255) DEFAULT NULL, record_sub_id VARCHAR(255) DEFAULT NULL, field_value VARCHAR(255) DEFAULT NULL, INDEX IDX_C6B7DA87A53A8AA (provider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trips (trip_id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, route_id VARCHAR(255) DEFAULT NULL, service_id VARCHAR(255) DEFAULT NULL, shape_id VARCHAR(255) DEFAULT NULL, trip_headsign LONGTEXT DEFAULT NULL, trip_short_name LONGTEXT DEFAULT NULL, direction_id ENUM(\'0\', \'1\'), block_id VARCHAR(255) DEFAULT NULL, wheelchair_accessible ENUM(\'0\', \'1\', \'2\'), bikes_allowed ENUM(\'0\', \'1\', \'2\'), INDEX IDX_AA7370DAA53A8AA (provider_id), INDEX IDX_AA7370DA34ECB4E6 (route_id), INDEX IDX_AA7370DAED5CA9E6 (service_id), INDEX IDX_AA7370DA50266CBB (shape_id), PRIMARY KEY(trip_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency ADD CONSTRAINT FK_70C0C6E6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE attributions ADD CONSTRAINT FK_14C967D2A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE calendar ADD CONSTRAINT FK_6EA9A146A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE calendar_dates ADD CONSTRAINT FK_C720CA70A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE calendar_dates ADD CONSTRAINT FK_C720CA70ED5CA9E6 FOREIGN KEY (service_id) REFERENCES calendar (service_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fare_attributes ADD CONSTRAINT FK_F5CBF19AA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fare_attributes ADD CONSTRAINT FK_F5CBF19AA048D2E2 FOREIGN KEY (fare_id) REFERENCES fare_rules (fare_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fare_attributes ADD CONSTRAINT FK_F5CBF19ACDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (agency_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fare_rules ADD CONSTRAINT FK_F1F0A80AA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fare_rules ADD CONSTRAINT FK_F1F0A80A34ECB4E6 FOREIGN KEY (route_id) REFERENCES routes (route_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fare_rules ADD CONSTRAINT FK_F1F0A80A56A273CC FOREIGN KEY (origin_id) REFERENCES stops (stop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fare_rules ADD CONSTRAINT FK_F1F0A80A816C6140 FOREIGN KEY (destination_id) REFERENCES stops (stop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feed_info ADD CONSTRAINT FK_4B1EDA00A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE frequencies ADD CONSTRAINT FK_282C52B8A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE frequencies ADD CONSTRAINT FK_282C52B8A5BC2E0E FOREIGN KEY (trip_id) REFERENCES trips (trip_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE levels ADD CONSTRAINT FK_9F2A6419A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pathways ADD CONSTRAINT FK_554EA1F8A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pathways ADD CONSTRAINT FK_554EA1F8BF5CE592 FOREIGN KEY (from_stop_id) REFERENCES stops (stop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pathways ADD CONSTRAINT FK_554EA1F8B79A3BC8 FOREIGN KEY (to_stop_id) REFERENCES stops (stop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE routes ADD CONSTRAINT FK_32D5C2B3A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE routes ADD CONSTRAINT FK_32D5C2B3CDEADB2A FOREIGN KEY (agency_id) REFERENCES agency (agency_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shapes ADD CONSTRAINT FK_93DBA512A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stations ADD CONSTRAINT FK_A7F775E9A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stop_route ADD CONSTRAINT FK_8F26CB4E34ECB4E6 FOREIGN KEY (route_id) REFERENCES routes (route_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stop_route ADD CONSTRAINT FK_8F26CB4E75E23604 FOREIGN KEY (town_id) REFERENCES town (town_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stop_route ADD CONSTRAINT FK_8F26CB4E3902063D FOREIGN KEY (stop_id) REFERENCES stops (stop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stop_times ADD CONSTRAINT FK_903505BBA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stop_times ADD CONSTRAINT FK_903505BBA5BC2E0E FOREIGN KEY (trip_id) REFERENCES trips (trip_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stop_times ADD CONSTRAINT FK_903505BB3902063D FOREIGN KEY (stop_id) REFERENCES stops (stop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stops ADD CONSTRAINT FK_39B58FA4A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stops ADD CONSTRAINT FK_39B58FA45FB14BA7 FOREIGN KEY (level_id) REFERENCES levels (level_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trafic ADD CONSTRAINT FK_D279A23A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trafic ADD CONSTRAINT FK_D279A2334ECB4E6 FOREIGN KEY (route_id) REFERENCES routes (route_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transfers ADD CONSTRAINT FK_802A3918A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transfers ADD CONSTRAINT FK_802A3918BF5CE592 FOREIGN KEY (from_stop_id) REFERENCES stops (stop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transfers ADD CONSTRAINT FK_802A3918B79A3BC8 FOREIGN KEY (to_stop_id) REFERENCES stops (stop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE translations ADD CONSTRAINT FK_C6B7DA87A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trips ADD CONSTRAINT FK_AA7370DAA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trips ADD CONSTRAINT FK_AA7370DA34ECB4E6 FOREIGN KEY (route_id) REFERENCES routes (route_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trips ADD CONSTRAINT FK_AA7370DAED5CA9E6 FOREIGN KEY (service_id) REFERENCES calendar (service_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trips ADD CONSTRAINT FK_AA7370DA50266CBB FOREIGN KEY (shape_id) REFERENCES shapes (shape_id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agency DROP FOREIGN KEY FK_70C0C6E6A53A8AA');
        $this->addSql('ALTER TABLE attributions DROP FOREIGN KEY FK_14C967D2A53A8AA');
        $this->addSql('ALTER TABLE calendar DROP FOREIGN KEY FK_6EA9A146A53A8AA');
        $this->addSql('ALTER TABLE calendar_dates DROP FOREIGN KEY FK_C720CA70A53A8AA');
        $this->addSql('ALTER TABLE calendar_dates DROP FOREIGN KEY FK_C720CA70ED5CA9E6');
        $this->addSql('ALTER TABLE fare_attributes DROP FOREIGN KEY FK_F5CBF19AA53A8AA');
        $this->addSql('ALTER TABLE fare_attributes DROP FOREIGN KEY FK_F5CBF19AA048D2E2');
        $this->addSql('ALTER TABLE fare_attributes DROP FOREIGN KEY FK_F5CBF19ACDEADB2A');
        $this->addSql('ALTER TABLE fare_rules DROP FOREIGN KEY FK_F1F0A80AA53A8AA');
        $this->addSql('ALTER TABLE fare_rules DROP FOREIGN KEY FK_F1F0A80A34ECB4E6');
        $this->addSql('ALTER TABLE fare_rules DROP FOREIGN KEY FK_F1F0A80A56A273CC');
        $this->addSql('ALTER TABLE fare_rules DROP FOREIGN KEY FK_F1F0A80A816C6140');
        $this->addSql('ALTER TABLE feed_info DROP FOREIGN KEY FK_4B1EDA00A53A8AA');
        $this->addSql('ALTER TABLE frequencies DROP FOREIGN KEY FK_282C52B8A53A8AA');
        $this->addSql('ALTER TABLE frequencies DROP FOREIGN KEY FK_282C52B8A5BC2E0E');
        $this->addSql('ALTER TABLE levels DROP FOREIGN KEY FK_9F2A6419A53A8AA');
        $this->addSql('ALTER TABLE pathways DROP FOREIGN KEY FK_554EA1F8A53A8AA');
        $this->addSql('ALTER TABLE pathways DROP FOREIGN KEY FK_554EA1F8BF5CE592');
        $this->addSql('ALTER TABLE pathways DROP FOREIGN KEY FK_554EA1F8B79A3BC8');
        $this->addSql('ALTER TABLE routes DROP FOREIGN KEY FK_32D5C2B3A53A8AA');
        $this->addSql('ALTER TABLE routes DROP FOREIGN KEY FK_32D5C2B3CDEADB2A');
        $this->addSql('ALTER TABLE shapes DROP FOREIGN KEY FK_93DBA512A53A8AA');
        $this->addSql('ALTER TABLE stations DROP FOREIGN KEY FK_A7F775E9A53A8AA');
        $this->addSql('ALTER TABLE stop_route DROP FOREIGN KEY FK_8F26CB4E34ECB4E6');
        $this->addSql('ALTER TABLE stop_route DROP FOREIGN KEY FK_8F26CB4E75E23604');
        $this->addSql('ALTER TABLE stop_route DROP FOREIGN KEY FK_8F26CB4E3902063D');
        $this->addSql('ALTER TABLE stop_times DROP FOREIGN KEY FK_903505BBA53A8AA');
        $this->addSql('ALTER TABLE stop_times DROP FOREIGN KEY FK_903505BBA5BC2E0E');
        $this->addSql('ALTER TABLE stop_times DROP FOREIGN KEY FK_903505BB3902063D');
        $this->addSql('ALTER TABLE stops DROP FOREIGN KEY FK_39B58FA4A53A8AA');
        $this->addSql('ALTER TABLE stops DROP FOREIGN KEY FK_39B58FA45FB14BA7');
        $this->addSql('ALTER TABLE trafic DROP FOREIGN KEY FK_D279A23A53A8AA');
        $this->addSql('ALTER TABLE trafic DROP FOREIGN KEY FK_D279A2334ECB4E6');
        $this->addSql('ALTER TABLE transfers DROP FOREIGN KEY FK_802A3918A53A8AA');
        $this->addSql('ALTER TABLE transfers DROP FOREIGN KEY FK_802A3918BF5CE592');
        $this->addSql('ALTER TABLE transfers DROP FOREIGN KEY FK_802A3918B79A3BC8');
        $this->addSql('ALTER TABLE translations DROP FOREIGN KEY FK_C6B7DA87A53A8AA');
        $this->addSql('ALTER TABLE trips DROP FOREIGN KEY FK_AA7370DAA53A8AA');
        $this->addSql('ALTER TABLE trips DROP FOREIGN KEY FK_AA7370DA34ECB4E6');
        $this->addSql('ALTER TABLE trips DROP FOREIGN KEY FK_AA7370DAED5CA9E6');
        $this->addSql('ALTER TABLE trips DROP FOREIGN KEY FK_AA7370DA50266CBB');
        $this->addSql('DROP TABLE agency');
        $this->addSql('DROP TABLE attributions');
        $this->addSql('DROP TABLE calendar');
        $this->addSql('DROP TABLE calendar_dates');
        $this->addSql('DROP TABLE fare_attributes');
        $this->addSql('DROP TABLE fare_rules');
        $this->addSql('DROP TABLE feed_info');
        $this->addSql('DROP TABLE frequencies');
        $this->addSql('DROP TABLE levels');
        $this->addSql('DROP TABLE messages');
        $this->addSql('DROP TABLE pathways');
        $this->addSql('DROP TABLE provider');
        $this->addSql('DROP TABLE routes');
        $this->addSql('DROP TABLE shapes');
        $this->addSql('DROP TABLE stations');
        $this->addSql('DROP TABLE stop_route');
        $this->addSql('DROP TABLE stop_times');
        $this->addSql('DROP TABLE stops');
        $this->addSql('DROP TABLE temp_stop_route');
        $this->addSql('DROP TABLE town');
        $this->addSql('DROP TABLE trafic');
        $this->addSql('DROP TABLE transfers');
        $this->addSql('DROP TABLE translations');
        $this->addSql('DROP TABLE trips');
    }
}
