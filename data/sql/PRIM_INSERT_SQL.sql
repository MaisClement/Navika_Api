TRUNCATE agency;
TRUNCATE routes;
TRUNCATE trips;
TRUNCATE calendar;
TRUNCATE calendar_dates;
TRUNCATE stop_times;
TRUNCATE stops;
TRUNCATE stop_times;
TRUNCATE transfers;
TRUNCATE pathways;
TRUNCATE zip_code;

LOAD DATA INFILE '/var/www/navika/data/file/gtfs/prim/agency.txt'			INTO TABLE agency FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/prim/calendar.txt'		    INTO TABLE calendar FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/prim/calendar_dates.txt'	INTO TABLE calendar_dates FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/prim/pathways.txt'		    INTO TABLE pathways FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/prim/routes.txt'			INTO TABLE routes FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/prim/stop_extensions.txt'	INTO TABLE stop_extensions FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/prim/stop_times.txt'		INTO TABLE stop_times FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/prim/stops.txt'			INTO TABLE stops FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/prim/transfers.txt'		INTO TABLE transfers FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/gtfs/prim/trips.txt'			INTO TABLE trips FIELDS TERMINATED BY ',' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;

LOAD DATA INFILE '/var/www/navika/data/file/geo/laposte_hexasmal.csv'		INTO TABLE zip_code FIELDS TERMINATED BY ';' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;


