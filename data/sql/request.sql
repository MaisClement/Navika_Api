---------------------
-- FONCTIONNE ! Pas de code postal (0.595 sec)
SELECT S.stop_id, S.stop_code, S.stop_name, GeomFromText(CONCAT('POINT (', S.stop_lat, ' ', S.stop_lon, ')')) AS geo_point, 0 AS distance, S.stop_desc, S.zone_id, S.level_id, S.platform_code, 
    CONCAT((   
        SELECT T.town_name
        FROM town T
        WHERE ST_CONTAINS(T.town_polygon, GeomFromText(CONCAT('POINT (', S.stop_lat, ' ', S.stop_lon, ')')))
    )) as town_name
FROM stops S

WHERE LOWER( S.stop_name ) LIKE '%Grignon%'
AND location_type = '1'

LIMIT 15;

---------------------
-- FONCTIONNE ! (0.690 sec)
SELECT S.stop_id, S.stop_code, S.stop_name, S.stop_lat, S.stop_lon, GeomFromText(CONCAT('POINT (', S.stop_lat, ' ', S.stop_lon, ')')) AS geo_point, 0 AS distance, S.stop_desc, S.zone_id, S.location_type, S.level_id, S.platform_code, 
CONCAT((
    SELECT CONCAT(Z.zip_code,'; ', T.town_name)
    FROM town T
    LEFT JOIN zip_code Z
    ON T.town_id = Z.town_id
    WHERE ST_CONTAINS(T.town_polygon, GeomFromText(CONCAT('POINT (', S.stop_lat, ' ', S.stop_lon, ')')))
    LIMIT 1
)) as town
FROM stops S

WHERE LOWER( S.stop_name ) LIKE '%Grignon%'
AND location_type = '1'

LIMIT 15; 

---------------------
-- FONCTIONNE ! (1.225 sec)
SELECT S.stop_id, S.stop_code, S.stop_name, GeomFromText(CONCAT('POINT (', S.stop_lat, ' ', S.stop_lon, ')')) AS geo_point, 0 AS distance, S.stop_desc, S.zone_id, S.level_id, S.platform_code, 
    CONCAT((
        SELECT T.town_name
        FROM town T
        WHERE ST_CONTAINS(T.town_polygon, GeomFromText(CONCAT('POINT (', S.stop_lat, ' ', S.stop_lon, ')')))
    )) as town_name,
    CONCAT((
        SELECT Z.zip_code
        FROM town T
        LEFT JOIN zip_code Z
        ON T.town_id = Z.town_id
        WHERE ST_CONTAINS(T.town_polygon, GeomFromText(CONCAT('POINT (', S.stop_lat, ' ', S.stop_lon, ')')))
    )) as zip_code
FROM stops S

WHERE LOWER( S.stop_name ) LIKE '%Grignon%'
AND location_type = '1'

LIMIT 15; 

---------------------
-- 
SELECT DISTINCT R.route_id, R.route_short_name, R.route_long_name, R.route_type, R.route_color, R.route_text_color
FROM routes R

LEFT JOIN trips T
ON R.route_id = T.route_id 

LEFT JOIN stop_times S
ON T.trip_id = S.trip_id

LEFT JOIN stops TT
ON S.stop_id = TT.stop_id

WHERE S.stop_id = 'IDFM:64199' OR TT.parent_station = 'IDFM:64199';

---------------------
-- 
SELECT DISTINCT R.route_id, R.route_short_name, R.route_long_name, R.route_type, R.route_color, R.route_text_color
FROM stop_times S

LEFT JOIN trips T
ON S.trip_id = T.trip_id

LEFT JOIN routes R
ON R.route_id = T.route_id

WHERE S.stop_id = 'IDFM:64199' OR parent_station = 'IDFM:64199';

SELECT stop_id, stop_name FROM stops WHERE stop_id IN (
  SELECT DISTINCT stop_id FROM stop_times WHERE trip_id IN (
    SELECT trip_id FROM trips WHERE route_id = <route_id>));

select * from routes where trips IN (
    select * from stop_times where stop_id IN (
        select stop_id from stops where stop_id = 'IDFM:64199' OR parent_station = 'IDFM:64199'
    )
)