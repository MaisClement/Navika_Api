TRUNCATE agency;
TRUNCATE calendar;
TRUNCATE calendar_dates;
TRUNCATE pathways;
TRUNCATE routes;
TRUNCATE stop_extensions;
TRUNCATE stop_times;
TRUNCATE stops;
TRUNCATE transfers;
TRUNCATE trips;

LOAD DATA INFILE '/var/www/navika/data/file/gtfs/agency.txt'			INTO TABLE agency FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/calendar.txt'		    INTO TABLE calendar FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/calendar_dates.txt'	INTO TABLE calendar_dates FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/pathways.txt'		    INTO TABLE pathways FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/routes.txt'			INTO TABLE routes FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/stop_extensions.txt'	INTO TABLE stop_extensions FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/stop_times.txt'		INTO TABLE stop_times FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/stops.txt'			    INTO TABLE stops FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/transfers.txt'		    INTO TABLE transfers FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/trips.txt'			    INTO TABLE trips FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;



TRUNCATE poi;
LOAD DATA INFILE '/var/www/navika/data/file/poi.csv'			    INTO TABLE poi FIELDS TERMINATED BY '\t' ESCAPED BY '\b'; ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
--- 4157806 rows

DELETE FROM poi
WHERE name = '';
--- 1289024 rows

DELETE FROM poi
WHERE highway != ''
OR railway != ''
OR aerialway != ''
OR aerodrome != ''
OR aeroway != ''
OR aeroway != ''
OR amenity = 'bus_station'
--- 64085 rows


DELETE FROM poi
WHERE NOT ST_CONTAINS(T.town_polygon, point(lon, lat))