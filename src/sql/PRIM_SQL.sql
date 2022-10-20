DROP TABLE IF EXISTS agency;
CREATE TABLE `agency` (
	agency_id VARCHAR(255),
	agency_name VARCHAR(255) NOT NULL,
	agency_url VARCHAR(255) NOT NULL,
	agency_timezone VARCHAR(255) NOT NULL,
	agency_lang VARCHAR(255),
	agency_phone VARCHAR(255),
	agency_fare_url VARCHAR(255),
	agency_email VARCHAR(255)
);

DROP TABLE IF EXISTS routes;
CREATE TABLE `routes` (
    route_id VARCHAR(255)NOT NULL,
    agency_id VARCHAR(255),
    route_short_name VARCHAR(255),
    route_long_name VARCHAR(255),
    route_desc VARCHAR(255),
    route_type ENUM('0', '1', '2', '3', '4', '5', '6', '7', '11', '12') NOT NULL,
    route_url VARCHAR(255),
    route_color VARCHAR(8),
    route_text_color VARCHAR(8),
    route_sort_order VARCHAR(255)
);

DROP TABLE IF EXISTS trips;
CREATE TABLE `trips` (
    route_id VARCHAR(255)NOT NULL,
    service_id VARCHAR(255) NOT NULL,
    trip_id VARCHAR(255) NOT NULL,
    trip_headsign VARCHAR(255),
    trip_short_name VARCHAR(255),
    direction_id ENUM('0', '1'),
    block_id VARCHAR(255),
    shape_id VARCHAR(255),
    wheelchair_accessible ENUM('0', '1', '2'),
    bikes_allowed ENUM('0', '1', '2')
);

DROP TABLE IF EXISTS calendar;
CREATE TABLE `calendar` (
    service_id VARCHAR(255)NOT NULL,
    monday ENUM('0', '1', '2') NOT NULL,
    tuesday ENUM('0', '1', '2') NOT NULL,
    wednesday ENUM('0', '1', '2') NOT NULL,
    thrusday ENUM('0', '1', '2') NOT NULL,
    friday ENUM('0', '1', '2') NOT NULL,
    saturday ENUM('0', '1', '2') NOT NULL,
    sunday ENUM('0', '1', '2') NOT NULL,
    `start_date` VARCHAR(255),
    end_date VARCHAR(255)
);

DROP TABLE IF EXISTS calendar_dates;
CREATE TABLE `calendar_dates` (
	service_id VARCHAR(255) NOT NULL,
	`date` VARCHAR(255) NOT NULL,
	exception_type VARCHAR(255) NOT NULL
);

DROP TABLE IF EXISTS stop_times;
CREATE TABLE `stop_times` (
    trip_id VARCHAR(255) NOT NULL,
    arrival_time VARCHAR(255),
    departure_time VARCHAR(255),
    stop_id VARCHAR(255),
    stop_sequence VARCHAR(255),
    pickup_type ENUM('0', '1', '2', '3'),
    drop_off_type ENUM('0', '1', '2', '3'),
    local_zone_id VARCHAR(255),
    stop_headsign VARCHAR(255),
    timepoint ENUM('0', '1')
);

DROP TABLE IF EXISTS stops;
CREATE TABLE `stops` (
    stop_id VARCHAR(255)NOT NULL,
    stop_code VARCHAR(255),
    stop_name VARCHAR(255) NOT NULL,
    stop_desc VARCHAR(255),
    stop_lat VARCHAR(255) NOT NULL,
    stop_lon VARCHAR(255) NOT NULL,
    zone_id VARCHAR(255) NOT NULL,
    stop_url VARCHAR(255),
    location_type ENUM('0', '1', '2', '3', '4'),
    parent_station VARCHAR(255),
    wheelchair_boarding VARCHAR(255),
    stop_timezone VARCHAR(255),
    level_id VARCHAR(255),
    platform_code VARCHAR(255)
);

DROP TABLE IF EXISTS transfers;
CREATE TABLE `transfers` (
    from_stop_id VARCHAR(255) NOT NULL,
    to_stop_id VARCHAR(255) NOT NULL,
    transfer_type VARCHAR(255)NOT NULL,
    min_transfer_time VARCHAR(255)
);

DROP TABLE IF EXISTS stop_extensions;
CREATE TABLE `stop_extensions` (
    object_id VARCHAR(255) NOT NULL,
    object_system VARCHAR(255) NOT NULL,
    object_code VARCHAR(255)NOT NULL
);

DROP TABLE IF EXISTS pathways;
CREATE TABLE `pathways` (
    pathways_id VARCHAR(255) NOT NULL,
    from_stop_id VARCHAR(255)  NOT NULL,
    to_stop_id VARCHAR(255)  NOT NULL,
    pathway_mode ENUM('0', '1', '2', '3', '4', '5', '6'),
    is_bidirectional ENUM('0', '1'),
    `length` VARCHAR(255),
    transversal_time VARCHAR(255),
    stair_count VARCHAR(255),
    max_slope VARCHAR(255),
    min_windth VARCHAR(255),
    signposted_as VARCHAR(255),
    reversed_signposted_as VARCHAR(255)
);

