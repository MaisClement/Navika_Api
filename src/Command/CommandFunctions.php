<?php

namespace App\Command;

use Symfony\Component\HttpClient\HttpClient;

class CommandFunctions
{
    public static function getGTFSDataFromApi($gtfs){
        $url = 'https://transport.data.gouv.fr/api/datasets/' . $gtfs->getUrl();
        
        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200){
            return;
        }

        $content = $response->getContent();
        $results = json_decode($content);

        foreach ($results->history as $history) {
            if ($history->payload->format == 'GTFS') {
                return array(
                    'provider_id'   => $gtfs->getId(),
                    'slug'          => $results->aom->name,
                    'title'         => $gtfs->getName(),
                    'type'          => $history->payload->format,
                    'url'           => $history->payload->resource_url,
                    'filenames'     => $history->payload->filenames,
                    'updated'       => date('Y-m-d H:i:s', strtotime($history->updated_at)),
                    'flag'          => 0,
                );
            }
        }

        return [];
    }

    public static function initDBUpdate($db){
        $req = $db->prepare("
            SET FOREIGN_KEY_CHECKS=0;
        ");
        $req->execute( [] );
        return $req;
    }

    public static function endDBUpdate($db){
        $req = $db->prepare("
        SET FOREIGN_KEY_CHECKS=1;
        ");
        $req->execute( [] );
        return $req;
    }

    public static function clearProviderData($db, $provider_id){
        $req = $db->prepare("
            DELETE FROM attributions    WHERE provider_id = ?;
            DELETE FROM translations    WHERE provider_id = ?;
            DELETE FROM feed_info       WHERE provider_id = ?;
            DELETE FROM frequencies     WHERE provider_id = ?;
            DELETE FROM fare_attributes WHERE provider_id = ?;
            DELETE FROM fare_rules      WHERE provider_id = ?;
            DELETE FROM stop_times      WHERE provider_id = ?;
            DELETE FROM pathways        WHERE provider_id = ?;
            DELETE FROM transfers       WHERE provider_id = ?;
            DELETE FROM stops           WHERE provider_id = ?;
            DELETE FROM levels          WHERE provider_id = ?;
            DELETE FROM trips           WHERE provider_id = ?;
            DELETE FROM shapes          WHERE provider_id = ?;
            DELETE FROM calendar_dates  WHERE provider_id = ?;
            DELETE FROM calendar        WHERE provider_id = ?;
            DELETE FROM routes          WHERE provider_id = ?;
            DELETE FROM agency          WHERE provider_id = ?;
        ");
        $req->execute(array($provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id, $provider_id));
        return $req;
    }

    public static function clearProviderDataInTable($db, $table, $provider_id){
        $req = $db->prepare("
            DELETE FROM $table    WHERE provider_id = ?;
        ");
        $req->execute( array( $provider_id ) );
        return $req;
    }

    public static function perpareTempTable($db, $table, $temp_table){
        $req = $db->prepare("
            DROP TABLE IF EXISTS $temp_table;
            CREATE TABLE $temp_table LIKE $table;

            SELECT MAX(AUTO_INCREMENT + 1) INTO @AutoInc
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table';

            SET @s:=CONCAT('ALTER TABLE $temp_table AUTO_INCREMENT=', @AutoInc);
            PREPARE stmt FROM @s;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        $req->execute( );
        return $req;
    }

    public static function insertFile($db, $type, $path, $header, $set, $sep = ','){
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
        $req->execute( [$path, $sep] );
        return $req;
    }

    public static function prefixTable($db, $table, $column, $prefix){
        $prefix_ch = $prefix . '%';
    
        $req = $db->prepare("
            UPDATE $table
            SET $column = CASE
                WHEN $column IS NOT NULL AND $column != '' AND $column NOT LIKE ? THEN CONCAT(?, $column)
                ELSE $column
                END;
        ");
        $req->execute( [$prefix_ch, $prefix] );
        return $req;
    }

    public static function copyTable($db, $from, $to){
        $req = $db->prepare("
            INSERT IGNORE INTO $to 
            SELECT * 
            FROM $from;
    
            DROP TABLE $from;
        ");
        $req->execute( [] );
        return $req;
    }

    public static function truncateTempStopRoute($db){
        $req = $db->prepare("
            TRUNCATE temp_stop_route;
        ");
        $req->execute( [] );
        return $req;
    }

    public static function generateTempStopRoute($db){
        $req = $db->prepare("
            INSERT INTO temp_stop_route
            (route_key, route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon)

            SELECT DISTINCT 
            CONCAT(R.route_id, '-', S2.stop_id) as route_key, R.route_id, R.route_short_name, R.route_long_name, R.route_type, R.route_color, R.route_text_color,
            S2.stop_id, S2.stop_name, S2.stop_name, S2.stop_lat, S2.stop_lon
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
        $req->execute( [] );
        return $req;
    }

    public static function autoDeleteStopRoute($db){
        $req = $db->prepare("
            DELETE FROM stop_route 
            WHERE route_key NOT IN (SELECT route_key FROM temp_stop_route);
        ");
        $req->execute( [] );
        return $req;
    }
    
    public static function autoInsertStopRoute($db){
        $req = $db->prepare("
            INSERT INTO stop_route (route_key, route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon, town_id, town_name, town_query_name, zip_code)
            
            SELECT route_key, route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon, town_id, town_name, town_query_name, zip_code
            FROM temp_stop_route
            WHERE route_key NOT IN (
                SELECT route_key
                FROM stop_route
            );
        ");
        $req->execute( [] );
        return $req;
    }

    public static function prepareStopRoute($db){
        $req = $db->prepare("
            UPDATE stop_route SR 
            SET SR.stop_lat = NULL,
                SR.stop_lon = NULL
            WHERE SR.stop_lat IS NULL;
            
            UPDATE stop_route SR 
            SET SR.stop_lat = NULL,
                SR.stop_lon = NULL
            WHERE SR.stop_lon IS NULL;
            
            UPDATE stop_route SR 
            SET SR.stop_lat = NULL,
                SR.stop_lon = NULL
            WHERE SR.stop_lat = '';
            
            UPDATE stop_route SR 
            SET SR.stop_lat = NULL,
                SR.stop_lon = NULL
            WHERE SR.stop_lon = '';
        ");
        $req->execute(array());
        return $req;
    }
    
    public static function generateTownInStopRoute($db){
        $req = $db->prepare("
            UPDATE stop_route SR 

            INNER JOIN town T
            ON ST_Contains(
                T.town_polygon,
                point(SR.stop_lat, SR.stop_lon)
            )
            
            SET SR.town_id = T.town_id,
                SR.town_name = T.town_name,
                SR.town_query_name = T.town_name,
                SR.zip_code = T.zip_code
                
            WHERE SR.town_id IS NULL;
        ");
        $req->execute(array());
        return $req;
    }

    public static function generateQueryRoute($db){
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

    public static function getColumn($db, $table) {
        $query = "
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'
        ";
        $statement = $db->executeQuery($query);
        $results = $statement->fetchAll();
        return $results;
    }
}