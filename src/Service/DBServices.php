<?php

namespace App\Service;

class DBServices
{
    public function __construct()
    {
        
    }

    public function initDBUpdate($db)
    {
        $req = $db->prepare("SET FOREIGN_KEY_CHECKS=0;");
        $req->execute([]);
        return $req;
    }

    public function endDBUpdate($db)
    {
        $req = $db->prepare("SET FOREIGN_KEY_CHECKS=1;");
        $req->execute([]);
        return $req;
    }

    public function clearProviderDataInTable($db, $table, $provider_id)
    {
        $req = $db->prepare("
            DELETE FROM $table
            WHERE provider_id = ?;
        ");
        $req->execute(array($provider_id));
        return $req;
    }

    public function perpareTempTable($db, $table, $temp_table)
    {
        $req = $db->prepare("
            DROP TABLE IF EXISTS $temp_table;
            CREATE TEMPORARY TABLE $temp_table LIKE $table;

            SELECT MAX(AUTO_INCREMENT + 1) INTO @AutoInc
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table';

            SET @s:=CONCAT('ALTER TABLE $temp_table AUTO_INCREMENT=', @AutoInc);
            PREPARE stmt FROM @s;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        $req->execute();
        return $req;
    }
    
    public function insertFile($db, $type, $path, $header, $set, $sep = ',')
    {
        $table = $type;
        $path = realpath($path);

        $req = $db->prepare("
            LOAD DATA INFILE ? IGNORE
            INTO TABLE $table
            FIELDS
                TERMINATED BY ?
                ENCLOSED BY '\"'
            LINES
                TERMINATED BY  '\n'
            IGNORE 1 ROWS
            ($header)
            SET $set
        ");
        $req->execute([$path, $sep]);
        return $req;
    }

    public function prefixTable($db, $table, $column, $prefix)
    {
        $prefix_ch = $prefix . '%';

        $req = $db->prepare("
            UPDATE $table
            SET $column = CONCAT(?, $column)
            WHERE $column NOT LIKE ?;
        ");
        $req->execute([$prefix, $prefix_ch]);
        return $req;
    }

    public function copyTable($db, $from, $to)
    {
        $req = $db->prepare("
            INSERT INTO $to 
            SELECT * 
            FROM $from;
    
            DROP TABLE $from;
        ");
        $req->execute([]);
        return $req;
    }

    public function truncateTable($db, $table)
    {
        $req = $db->prepare("
            TRUNCATE $table;
        ");
        $req->execute([]);
        return $req;
    }

    public function generateTempStopRoute($db)
    {
        $req = $db->prepare("
            INSERT INTO temp_stop_route
            (route_key, route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon, location_type)
            
            SELECT DISTINCT 
            CONCAT(R.route_id, '-', S2.stop_id, '-', S2.route_short_name, '-', S2.route_long_name, '-', S2.route_color, '-', S2.route_text_color) as route_key, R.route_id, R.route_short_name, R.route_long_name, R.route_type, R.route_color, R.route_text_color, S2.stop_id, S2.stop_name, S2.stop_name, S2.stop_lat, S2.stop_lon, S2.location_type
            FROM routes R
            
            INNER JOIN trips T
            ON R.route_id = T.route_id
            
            INNER JOIN stop_times ST
            ON T.trip_id = ST.trip_id
            
            INNER JOIN stops S
            ON ST.stop_id = S.stop_id
            
            INNER JOIN stops S2
            ON S.parent_station = S2.stop_id;
        ");
        $req->execute([]);
        return $req;
    }

    public function generateTempStopRoute2($db)
    {
        $req = $db->prepare("
            INSERT INTO temp_stop_route
            (route_key, route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon, location_type)
            
            SELECT DISTINCT 
            CONCAT(R.route_id, '-', S.stop_id, '-', S.route_short_name, '-', S.route_long_name, '-', S.route_color, '-', S.route_text_color) as route_key, R.route_id, R.route_short_name, R.route_long_name, R.route_type, R.route_color, R.route_text_color, S.stop_id, S.stop_name, S.stop_name, S.stop_lat, S.stop_lon, S.location_type
            FROM routes R
            
            INNER JOIN trips T
            ON R.route_id = T.route_id
            
            INNER JOIN stop_times ST
            ON T.trip_id = ST.trip_id
            
            INNER JOIN stops S
            ON ST.stop_id = S.stop_id

            WHERE location_type = '0'
            AND ST.pickup_type != '1';
        ");
        $req->execute([]);
        return $req;
    }

    public function autoDeleteStopRoute($db)
    {
        $req = $db->prepare("
            DELETE FROM stop_route 
            WHERE route_key NOT IN (SELECT route_key FROM temp_stop_route);
        ");
        $req->execute([]);
        return $req;
    }

    public function autoInsertStopRoute($db)
    {
        $req = $db->prepare("
            INSERT INTO stop_route (route_key, route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon, town_id, town_name, town_query_name, zip_code, location_type)
            
            SELECT route_key, route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon, town_id, town_name, town_query_name, zip_code, location_type
            FROM temp_stop_route
            WHERE route_key NOT IN (
                SELECT route_key
                FROM stop_route
            );
        ");
        $req->execute([]);
        return $req;
    }

    public function prepareStopRoute($db)
    {
        $req = $db->prepare("
            UPDATE stop_route SR 
            SET SR.stop_lat = NULL,
                SR.stop_lon = NULL
            WHERE SR.stop_lat IS NULL
                OR SR.stop_lon IS NULL
                OR SR.stop_lat = ''
                OR SR.stop_lon = '';
        ");
        $req->execute(array());
        return $req;
    }

    public function generateQueryRoute($db)
    {
        $req = $db->prepare("
            SET NAMES 'utf8' COLLATE 'utf8_unicode_ci';
            UPDATE stop_route SET stop_query_name = REPLACE(stop_query_name, '-', '');
            UPDATE stop_route SET stop_query_name = REPLACE(stop_query_name, ' ', '');
            UPDATE stop_route SET stop_query_name = REPLACE(stop_query_name, '\'', '');
            
            UPDATE stop_route SET town_query_name = REPLACE(town_query_name, '-', '');
            UPDATE stop_route SET town_query_name = REPLACE(town_query_name, ' ', '');
            UPDATE stop_route SET town_query_name = REPLACE(town_query_name, '\'', '');
        ");
        $req->execute(array());
        return $req;
    }

    public function getColumns($db, $table)
    {
        $query = "
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'
        ";
        $statement = $db->executeQuery($query);
        return $statement->fetchAll();
    }
}
