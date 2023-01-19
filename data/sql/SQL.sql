DROP TABLE IF EXISTS agency;
CREATE TABLE `agency` (
  agency_id         VARCHAR(255) PRIMARY KEY NOT NULL,
  agency_name       TEXT NOT NULL,
  agency_url        TEXT NOT NULL,
  agency_timezone   TEXT NOT NULL,
  agency_lang       TEXT,
  agency_phone      TEXT,
  agency_fare_url   TEXT,
  agency_email      TEXT
);

DROP TABLE IF EXISTS stops;
CREATE TABLE `stops` (
  stop_id               VARCHAR(255) PRIMARY KEY NOT NULL,
  stop_code             TEXT,
  stop_name             TEXT NOT NULL,
  stop_desc             TEXT,
  stop_lat              TEXT NOT NULL,
  stop_lon              TEXT NOT NULL,
  zone_id               TEXT,
  stop_url              TEXT,
  location_type         ENUM('0', '1', '2', '3', '4'),
  parent_station        TEXT,
  stop_timezone         TEXT,
  wheelchair_boarding   ENUM('0', '1', '2'),
  level_id              TEXT,
  platform_code         TEXT
);

DROP TABLE IF EXISTS routes;
CREATE TABLE `routes` (
  route_id              VARCHAR(255) PRIMARY KEY NOT NULL,
  agency_id             TEXT NOT NULL,
  route_short_name      TEXT,
  route_long_name       TEXT,
  route_desc            TEXT,
  route_type            ENUM('0', '1', '2', '3', '4', '5', '6', '7', '11', '12') NOT NULL,
  route_url             TEXT,
  route_color           VARCHAR(8),
  route_text_color      VARCHAR(8),
  route_sort_order      INT,
  continuous_pickup     ENUM('0', '1', '2', '3'),
  continuous_drop_off   ENUM('0', '1', '2', '3')
);

DROP TABLE IF EXISTS trips;
CREATE TABLE `trips` (
  route_id              TEXT NOT NULL,
  service_id            TEXT NOT NULL,
  trip_id               VARCHAR(255) PRIMARY KEY NOT NULL,
  trip_headsign         TEXT,
  trip_short_name       TEXT,
  direction_id          ENUM('0', '1'),
  block_id              TEXT,
  shape_id              TEXT,
  wheelchair_accessible ENUM('0', '1', '2'),
  bikes_allowed         ENUM('0', '1', '2')
);

DROP TABLE IF EXISTS stop_times;
CREATE TABLE `stop_times` (
  trip_id               TEXT NOT NULL,
  arrival_time          TIME,
  departure_time        TIME,
  stop_id               TEXT NOT NULL,
  stop_sequence         INT NOT NULL,
  stop_headsign         TEXT,
  pickup_type           ENUM('0', '1', '2', '3'),
  drop_off_type         ENUM('0', '1', '2', '3'),
  continuous_pickup     ENUM('0', '1', '2', '3'),
  continuous_drop_off   ENUM('0', '1', '2', '3'),
  shape_dist_traveled   DOUBLE,
  timepoint             ENUM('0', '1')        
);

DROP TABLE IF EXISTS calendar;
CREATE TABLE `calendar` (
    service_id          VARCHAR(255) PRIMARY KEY NOT NULL,
    monday              ENUM('0', '1', '2') NOT NULL,
    tuesday             ENUM('0', '1', '2') NOT NULL,
    wednesday           ENUM('0', '1', '2') NOT NULL,
    thrusday            ENUM('0', '1', '2') NOT NULL,
    friday              ENUM('0', '1', '2') NOT NULL,
    saturday            ENUM('0', '1', '2') NOT NULL,
    sunday              ENUM('0', '1', '2') NOT NULL,
    start_date          DATE,
    end_date            DATE
);

DROP TABLE IF EXISTS calendar_dates;
CREATE TABLE `calendar_dates` (
  service_id            VARCHAR(255) PRIMARY KEY NOT NULL,
  `date`                DATE NOT NULL,
  exception_type        ENUM('0', '1') NOT NULL
);

DROP TABLE IF EXISTS fare_attributes;
CREATE TABLE `fare_attributes` (
  fare_id               VARCHAR(255) PRIMARY KEY NOT NULL,
  price                 DOUBLE NOT NULL,
  currency_type         TEXT NOT NULL,
  payment_method        ENUM('0', '1') NOT NULL,
  transfers             ENUM('0', '1', '2') NOT NULL,
  agency_id             TEXT,
  transfer_duration     INT
);

DROP TABLE IF EXISTS fare_rules;
CREATE TABLE `fare_rules` (
  fare_id               TEXT NOT NULL,
  route_id              TEXT, 
  origin_id             TEXT, 
  destination_id        TEXT, 
  contains_id           TEXT 
);

DROP TABLE IF EXISTS shapes;
CREATE TABLE `shapes` (
  shape_id              VARCHAR(255) PRIMARY KEY NOT NULL,
  shape_pt_lat          TEXT NOT NULL,
  shape_pt_lon          TEXT NOT NULL,
  shape_pt_sequence     INT NOT NULL,
  shape_dist_traveled   DOUBLE
);

DROP TABLE IF EXISTS frequencies;
CREATE TABLE `frequencies` (
  trip_id               TEXT NOT NULL,
  start_time            TIME NOT NULL,
  end_time              TIME NOT NULL,
  headway_secs          INT NOT NULL,
  exact_times           ENUM('0', '1') NOT NULL
);

DROP TABLE IF EXISTS transfers;
CREATE TABLE `transfers` (
  from_stop_id          TEXT NOT NULL,
  to_stop_id            TEXT NOT NULL,
  transfer_type         ENUM('0', '1', '2', '3', '4', '5') NOT NULL,
  min_transfer_time     INT
);

DROP TABLE IF EXISTS pathways;
CREATE TABLE `pathways` (
  pathway_id              VARCHAR(255) PRIMARY KEY NOT NULL,
  from_stop_id            TEXT NOT NULL,
  to_stop_id              TEXT NOT NULL,
  pathway_mode            ENUM('0', '1', '2', '3', '4', '5', '6', '7') NOT NULL,
  is_bidirectional        ENUM('0', '1') NOT NULL,
  length                  DOUBLE,
  traversal_time          INT,
  stair_count             INT,
  max_slope               DOUBLE,
  min_width               DOUBLE,
  signposted_as           TEXT,
  reversed_signposted_as  TEXT
);

DROP TABLE IF EXISTS levels;
CREATE TABLE `levels` (
  level_id                VARCHAR(255) PRIMARY KEY NOT NULL,
  level_index             DOUBLE NOT NULL,
  level_name              TEXT        
);

DROP TABLE IF EXISTS feed_info;
CREATE TABLE `feed_info` (
  feed_publisher_name     TEXT NOT NULL,
  feed_publisher_url      TEXT NOT NULL,
  feed_lang               TEXT NOT NULL,
  default_lang            TEXT,
  feed_start_date         Date,
  feed_end_date           Date,
  feed_version            TEXT,
  feed_contact_email      TEXT,
  feed_contact_url        TEXT
);

DROP TABLE IF EXISTS translations;
CREATE TABLE `translations` (
  table_name        ENUM('agency', 'stops', 'routes', 'trips', 'stop_times', 'feed_info', 'pathways', 'levels', 'attributions') NOT NULL,
  field_name        TEXT NOT NULL,
  language          TEXT NOT NULL,
  translation       TEXT NOT NULL,
  record_id         TEXT,
  record_sub_id     TEXT,
  field_value       TEXT
);

DROP TABLE IF EXISTS attributions;
CREATE TABLE `attributions` (
  attribution_id     TEXT,
  agency_id          TEXT,
  route_id           TEXT,
  trip_id            TEXT,
  organization_name  TEXT NOT NULL,
  is_producer        ENUM('0', '1'),
  is_operator        ENUM('0', '1'),
  is_authority       ENUM('0', '1'),
  attribution_url    TEXT ,
  attribution_email  TEXT,
  attribution_phone  TEXT
);


-- ALTER TABLE routes
-- ADD CONSTRAINT FK_agency_id
-- FOREIGN KEY  (agency_id) REFERENCES agency(agency_id); 
-- 
-- ALTER TABLE trips
-- ADD CONSTRAINT FK_route_id
-- FOREIGN KEY  (route_id) REFERENCES routes(route_id); 
-- 
-- ALTER TABLE stop_times
-- ADD CONSTRAINT FK_trip_id
-- FOREIGN KEY  (trip_id) REFERENCES trips(trip_id); 
-- ALTER TABLE stop_times
-- ADD CONSTRAINT FK_stop_id
-- FOREIGN KEY  (stop_id) REFERENCES stops(stop_id); 
-- 
-- ALTER TABLE frequencies
-- ADD CONSTRAINT FK_trip_id
-- FOREIGN KEY  (trip_id) REFERENCES trips(trip_id); 
-- 
-- ALTER TABLE transfers
-- ADD CONSTRAINT FK_from_stop_id
-- FOREIGN KEY  (from_stop_id) REFERENCES stops(stop_id); 
-- ALTER TABLE transfers
-- ADD CONSTRAINT FK_to_stop_id
-- FOREIGN KEY  (to_stop_id) REFERENCES stops(stop_id); 
-- 
-- ALTER TABLE pathways
-- ADD CONSTRAINT FK_from_stop_id
-- FOREIGN KEY  (from_stop_id) REFERENCES stops(stop_id); 
-- ALTER TABLE pathways
-- ADD CONSTRAINT FK_to_stop_id
-- FOREIGN KEY  (to_stop_id) REFERENCES stops(stop_id); 
-- 
-- ALTER TABLE fare_rules
-- ADD CONSTRAINT FK_fare_id
-- FOREIGN KEY  (fare_id) REFERENCES fare_attributes(fare_id); 
-- ALTER TABLE fare_rules
-- ADD CONSTRAINT FK_route_id
-- FOREIGN KEY  (route_id) REFERENCES routes(route_id); 
-- ALTER TABLE fare_rules
-- ADD CONSTRAINT FK_origin_id
-- FOREIGN KEY  (origin_id) REFERENCES stops(zone_id); 
-- ALTER TABLE fare_rules
-- ADD CONSTRAINT FK_destination_id
-- FOREIGN KEY  (destination_id) REFERENCES stops(zone_id); 
-- ALTER TABLE fare_rules
-- ADD CONSTRAINT FK_contains_id
-- FOREIGN KEY  (contains_id) REFERENCES stops(zone_id); 