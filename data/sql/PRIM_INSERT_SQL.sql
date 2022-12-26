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
LOAD DATA INFILE '/var/www/navika/data/file/geo/laposte_hexasmal.csv'		INTO TABLE zip_code FIELDS TERMINATED BY ';' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;

TRUNCATE lignes;
TRUNCATE arrets_lignes;
LOAD DATA INFILE '/var/www/navika/data/file/lignes.csv'               		INTO TABLE lignes FIELDS TERMINATED BY ';' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;
LOAD DATA INFILE '/var/www/navika/data/file/arrets_lignes.csv'              INTO TABLE arrets_lignes FIELDS TERMINATED BY ';' ENCLOSED BY '"'LINES TERMINATED BY '\n'IGNORE 1 ROWS;

wget -O lignes.csv https://data.iledefrance-mobilites.fr/explore/dataset/referentiel-des-lignes/download/?format=csv&timezone=Europe/Berlin&lang=fr&use_labels_for_header=true&csv_separator=%3B
wget -O arrets_lignes.csv https://data.iledefrance-mobilites.fr/explore/dataset/arrets-lignes/download/?format=csv&timezone=Europe/Berlin&lang=fr&use_labels_for_header=true&csv_separator=%3B