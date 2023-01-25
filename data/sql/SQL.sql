DROP TABLE IF EXISTS provider;
CREATE TABLE `provider` (
  provider_id   VARCHAR(255) NOT NULL,
  slug          VARCHAR(255) NOT NULL,
  title         VARCHAR(255) NOT NULL,
  type          VARCHAR(255) NOT NULL,
  url           VARCHAR(255) NOT NULL,
  updated       DATETIME NOT NULL,
  flag          ENUM('0', '1', '2') NOT NULL
);

DROP TABLE IF EXISTS agency;
CREATE TABLE `agency` (
  provider_id       VARCHAR(255) NOT NULL,
  agency_id         VARCHAR(255) PRIMARY KEY NOT NULL,
  agency_name       VARCHAR(255) NOT NULL,
  agency_url        VARCHAR(255) NOT NULL,
  agency_timezone   VARCHAR(255) NOT NULL,
  agency_lang       VARCHAR(255),
  agency_phone      VARCHAR(255),
  agency_fare_url   VARCHAR(255),
  agency_email      VARCHAR(255)
);

DROP TABLE IF EXISTS stops;
CREATE TABLE `stops` (
  provider_id           VARCHAR(255) NOT NULL,
  stop_id               VARCHAR(255) PRIMARY KEY NOT NULL,
  stop_code             VARCHAR(255),
  stop_name             VARCHAR(255) NOT NULL,
  stop_desc             VARCHAR(255),
  stop_lat              VARCHAR(255) NOT NULL,
  stop_lon              VARCHAR(255) NOT NULL,
  zone_id               VARCHAR(255),
  stop_url              VARCHAR(255),
  location_type         ENUM('0', '1', '2', '3', '4'),
  parent_station        VARCHAR(255),
  stop_timezone         VARCHAR(255),
  wheelchair_boarding   ENUM('0', '1', '2'),
  level_id              VARCHAR(255),
  platform_code         VARCHAR(255)
);

DROP TABLE IF EXISTS routes;
CREATE TABLE `routes` (
  provider_id       VARCHAR(255) NOT NULL,
  route_id              VARCHAR(255) PRIMARY KEY NOT NULL,
  agency_id             VARCHAR(255) NOT NULL,
  route_short_name      TEXT,
  route_long_name       TEXT,
  route_desc            TEXT,
  route_type            VARCHAR(255), -- ENUM('0', '1', '2', '3', '4', '5', '6', '7', '11', '12') NOT NULL,
  route_url             VARCHAR(10),
  route_color           VARCHAR(8),
  route_text_color      VARCHAR(8),
  route_sort_order      VARCHAR(8),
  continuous_pickup     ENUM('0', '1', '2', '3'),
  continuous_drop_off   ENUM('0', '1', '2', '3')
);

DROP TABLE IF EXISTS trips;
CREATE TABLE `trips` (
  provider_id           VARCHAR(255) NOT NULL,
  route_id              VARCHAR(255) NOT NULL,
  service_id            VARCHAR(255) NOT NULL,
  trip_id               VARCHAR(255) PRIMARY KEY NOT NULL,
  trip_headsign         TEXT,
  trip_short_name       TEXT,
  direction_id          ENUM('0', '1'),
  block_id              VARCHAR(255),
  shape_id              VARCHAR(255),
  wheelchair_accessible ENUM('0', '1', '2'),
  bikes_allowed         ENUM('0', '1', '2')
);

DROP TABLE IF EXISTS stop_times;
CREATE TABLE `stop_times` (
  provider_id           VARCHAR(255) NOT NULL,
  trip_id               VARCHAR(255) NOT NULL,
  arrival_time          TIME,
  departure_time        TIME,
  stop_id               VARCHAR(255) NOT NULL,
  stop_sequence         INT NOT NULL,
  stop_headsign         VARCHAR(255),
  local_zone_id         VARCHAR(255),
  pickup_type           ENUM('0', '1', '2', '3'),
  drop_off_type         ENUM('0', '1', '2', '3'),
  continuous_pickup     ENUM('0', '1', '2', '3'),
  continuous_drop_off   ENUM('0', '1', '2', '3'),
  shape_dist_traveled   DOUBLE,
  timepoint             ENUM('0', '1')        
);

DROP TABLE IF EXISTS calendar;
CREATE TABLE `calendar` (
    provider_id         VARCHAR(255) NOT NULL,
    service_id          VARCHAR(255) PRIMARY KEY NOT NULL,
    monday              ENUM('0', '1', '2') NOT NULL,
    tuesday             ENUM('0', '1', '2') NOT NULL,
    wednesday           ENUM('0', '1', '2') NOT NULL,
    thursday            ENUM('0', '1', '2') NOT NULL,
    friday              ENUM('0', '1', '2') NOT NULL,
    saturday            ENUM('0', '1', '2') NOT NULL,
    sunday              ENUM('0', '1', '2') NOT NULL,
    start_date          DATE,
    end_date            DATE
);

DROP TABLE IF EXISTS calendar_dates;
CREATE TABLE `calendar_dates` (
  provider_id           VARCHAR(255) NOT NULL,
  service_id            VARCHAR(255) NOT NULL,
  `date`                DATE NOT NULL,
  exception_type        ENUM('0', '1') NOT NULL
);

DROP TABLE IF EXISTS fare_attributes;
CREATE TABLE `fare_attributes` (
  provider_id       VARCHAR(255) NOT NULL,
  fare_id               VARCHAR(255) PRIMARY KEY NOT NULL,
  price                 DOUBLE NOT NULL,
  currency_type         VARCHAR(255) NOT NULL,
  payment_method        ENUM('0', '1') NOT NULL,
  transfers             ENUM('0', '1', '2') NOT NULL,
  agency_id             VARCHAR(255),
  transfer_duration     INT
);

DROP TABLE IF EXISTS fare_rules;
CREATE TABLE `fare_rules` (
  provider_id       VARCHAR(255) NOT NULL,
  fare_id               VARCHAR(255) NOT NULL,
  route_id              VARCHAR(255), 
  origin_id             VARCHAR(255), 
  destination_id        VARCHAR(255), 
  contains_id           VARCHAR(255) 
);

DROP TABLE IF EXISTS shapes;
CREATE TABLE `shapes` (
  provider_id           VARCHAR(255) NOT NULL,
  shape_id              VARCHAR(255) NOT NULL,
  shape_pt_lat          VARCHAR(255) NOT NULL,
  shape_pt_lon          VARCHAR(255) NOT NULL,
  shape_pt_sequence     INT NOT NULL,
  shape_dist_traveled   DOUBLE
);

DROP TABLE IF EXISTS frequencies;
CREATE TABLE `frequencies` (
  provider_id       VARCHAR(255) NOT NULL,
  trip_id               VARCHAR(255) NOT NULL,
  start_time            TIME NOT NULL,
  end_time              TIME NOT NULL,
  headway_secs          INT NOT NULL,
  exact_times           ENUM('0', '1') NOT NULL
);

DROP TABLE IF EXISTS transfers;
CREATE TABLE `transfers` (
  provider_id       VARCHAR(255) NOT NULL,
  from_stop_id          VARCHAR(255) NOT NULL,
  to_stop_id            VARCHAR(255) NOT NULL,
  transfer_type         ENUM('0', '1', '2', '3', '4', '5') NOT NULL,
  min_transfer_time     INT
);

DROP TABLE IF EXISTS pathways;
CREATE TABLE `pathways` (
  provider_id       VARCHAR(255) NOT NULL,
  pathway_id              VARCHAR(255) PRIMARY KEY NOT NULL,
  from_stop_id            VARCHAR(255) NOT NULL,
  to_stop_id              VARCHAR(255) NOT NULL,
  pathway_mode            ENUM('0', '1', '2', '3', '4', '5', '6', '7') NOT NULL,
  is_bidirectional        ENUM('0', '1') NOT NULL,
  length                  DOUBLE,
  traversal_time          VARCHAR(8),
  stair_count             VARCHAR(8),
  max_slope               VARCHAR(8),
  min_width               VARCHAR(8),
  signposted_as           VARCHAR(255),
  reversed_signposted_as  VARCHAR(255)
);

DROP TABLE IF EXISTS levels;
CREATE TABLE `levels` (
  provider_id       VARCHAR(255) NOT NULL,
  level_id                VARCHAR(255) PRIMARY KEY NOT NULL,
  level_index             VARCHAR(8) NOT NULL,
  level_name              VARCHAR(255)        
);

DROP TABLE IF EXISTS feed_info;
CREATE TABLE `feed_info` (
  provider_id       VARCHAR(255) NOT NULL,
  feed_publisher_name     VARCHAR(255) NOT NULL,
  feed_publisher_url      VARCHAR(255) NOT NULL,
  feed_lang               VARCHAR(255) NOT NULL,
  default_lang            VARCHAR(255),
  feed_start_date         Date,
  feed_end_date           Date,
  feed_version            VARCHAR(255),
  feed_contact_email      VARCHAR(255),
  feed_contact_url        VARCHAR(255)
);

DROP TABLE IF EXISTS translations;
CREATE TABLE `translations` (
  provider_id       VARCHAR(255) NOT NULL,
  table_name        ENUM('agency', 'stops', 'routes', 'trips', 'stop_times', 'feed_info', 'pathways', 'levels', 'attributions') NOT NULL,
  field_name        VARCHAR(255) NOT NULL,
  language          VARCHAR(255) NOT NULL,
  translation       VARCHAR(255) NOT NULL,
  record_id         VARCHAR(255),
  record_sub_id     VARCHAR(255),
  field_value       VARCHAR(255)
);

DROP TABLE IF EXISTS attributions;
CREATE TABLE `attributions` (
  provider_id        VARCHAR(255) NOT NULL,
  attribution_id     VARCHAR(255),
  agency_id          VARCHAR(255),
  route_id           VARCHAR(255),
  trip_id            VARCHAR(255),
  organization_name  VARCHAR(255) NOT NULL,
  is_producer        ENUM('0', '1'),
  is_operator        ENUM('0', '1'),
  is_authority       ENUM('0', '1'),
  attribution_url    VARCHAR(255) ,
  attribution_email  VARCHAR(255),
  attribution_phone  VARCHAR(255)
);

DROP TABLE IF EXISTS town;
CREATE TABLE `town` (
  town_id VARCHAR(255) PRIMARY KEY NOT NULL,
  town_name VARCHAR(255) NOT NULL,
  town_polygon polygon NOT NULL
);

DROP TABLE IF EXISTS stop_route;
CREATE TABLE `stop_route` (
  route_id              VARCHAR(255) NOT NULL,
  route_short_name      TEXT,
  route_long_name       TEXT,
  route_type            VARCHAR(255), -- ENUM('0', '1', '2', '3', '4', '5', '6', '7', '11', '12') NOT NULL,
  route_color           VARCHAR(8),
  route_text_color      VARCHAR(8),
  
  stop_id               VARCHAR(255) NOT NULL,
  stop_name             TEXT NOT NULL,
  stop_query_name       TEXT NOT NULL,
  stop_lat              VARCHAR(255),
  stop_lon              VARCHAR(255),

  town_id               VARCHAR(255),
  town_name             VARCHAR(255),
  town_query_name       VARCHAR(255)
);

CREATE FULLTEXT INDEX stop_times_trip_id ON stop_times(trip_id);
CREATE FULLTEXT INDEX stop_times_stop_id ON stop_times(stop_id);

CREATE FULLTEXT INDEX stop_route_stop_id ON stop_route(stop_id);
CREATE FULLTEXT INDEX stop_route_query ON stop_route(stop_query_name);
CREATE FULLTEXT INDEX stop_route_query_town ON stop_route(town_query_name);
CREATE FULLTEXT INDEX stop_route_route_id ON stop_route(route_id);

CREATE SPATIAL INDEX town_polygon ON town(town_polygon);

-- ALTER TABLE agency
-- ADD CONSTRAINT FK_agency_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE stops
-- ADD CONSTRAINT FK_stops_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE routes
-- ADD CONSTRAINT FK_routes_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE trips
-- ADD CONSTRAINT FK_trips_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE stop_times
-- ADD CONSTRAINT FK_stop_times_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE calendar
-- ADD CONSTRAINT FK_calendar_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE calendar_dates
-- ADD CONSTRAINT FK_calendar_dates_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE fare_attributes
-- ADD CONSTRAINT FK_fare_attributes_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE fare_rules
-- ADD CONSTRAINT FK_fare_rules_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE shapes
-- ADD CONSTRAINT FK_shapes_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE frequencies
-- ADD CONSTRAINT FK_frequencies_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE transfers
-- ADD CONSTRAINT FK_transfers_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE pathways
-- ADD CONSTRAINT FK_pathways_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE levels
-- ADD CONSTRAINT FK_levels_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE feed_info
-- ADD CONSTRAINT FK_feed_info_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE translations
-- ADD CONSTRAINT FK_translations_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE attributions
-- ADD CONSTRAINT FK_attributions_provider_id
-- FOREIGN KEY  (provider_id) REFERENCES provider(provider_id) ON DELETE CASCADE; 
-- 
-- 
-- ALTER TABLE routes
-- ADD CONSTRAINT FK_routes_agency_id
-- FOREIGN KEY  (agency_id) REFERENCES agency(agency_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE trips
-- ADD CONSTRAINT FK_trips_route_id
-- FOREIGN KEY  (route_id) REFERENCES routes(route_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE stop_times
-- ADD CONSTRAINT FK_stop_times_trip_id
-- FOREIGN KEY  (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE; 
-- ALTER TABLE stop_times
-- ADD CONSTRAINT FK_stop_times_stop_id
-- FOREIGN KEY  (stop_id) REFERENCES stops(stop_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE frequencies
-- ADD CONSTRAINT FK_frequencies_trip_id
-- FOREIGN KEY  (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE transfers
-- ADD CONSTRAINT FK_transfers_from_stop_id
-- FOREIGN KEY  (from_stop_id) REFERENCES stops(stop_id) ON DELETE CASCADE; 
-- ALTER TABLE transfers
-- ADD CONSTRAINT FK_transfers_to_stop_id
-- FOREIGN KEY  (to_stop_id) REFERENCES stops(stop_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE pathways
-- ADD CONSTRAINT FK_pathways_from_stop_id
-- FOREIGN KEY  (from_stop_id) REFERENCES stops(stop_id) ON DELETE CASCADE; 
-- ALTER TABLE pathways
-- ADD CONSTRAINT FK_pathways_to_stop_id
-- FOREIGN KEY  (to_stop_id) REFERENCES stops(stop_id) ON DELETE CASCADE; 
-- 
-- ALTER TABLE fare_rules
-- ADD CONSTRAINT FK_fare_rules_fare_id
-- FOREIGN KEY  (fare_id) REFERENCES fare_attributes(fare_id) ON DELETE CASCADE; 
-- ALTER TABLE fare_rules
-- ADD CONSTRAINT FK_fare_rules_route_id
-- FOREIGN KEY  (route_id) REFERENCES routes(route_id) ON DELETE CASCADE; 
-- ALTER TABLE fare_rules
-- ADD CONSTRAINT FK_fare_rules_origin_id
-- FOREIGN KEY  (origin_id) REFERENCES stops(zone_id) ON DELETE CASCADE; 
-- ALTER TABLE fare_rules
-- ADD CONSTRAINT FK_fare_rules_destination_id
-- FOREIGN KEY  (destination_id) REFERENCES stops(zone_id) ON DELETE CASCADE; 
-- ALTER TABLE fare_rules
-- ADD CONSTRAINT FK_fare_rules_contains_id
-- FOREIGN KEY  (contains_id) REFERENCES stops(zone_id) ON DELETE CASCADE; 