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
-- FONCTIONNE ! Mais lent ET uniquement si une ville
-- (5.454 sec)
SELECT S.stop_id, S.stop_code, S.stop_name, GeomFromText(CONCAT('POINT (', S.stop_lat, ' ', S.stop_lon, ')')) AS geo_point, 0 AS distance, S.stop_desc, S.zone_id, S.level_id, S.platform_code, T.town_name
FROM town T

LEFT JOIN stops S
ON ST_CONTAINS(T.town_polygon, GeomFromText(CONCAT('POINT (', S.stop_lat, ' ', S.stop_lon, ')')))

WHERE LOWER( S.stop_name ) LIKE '%Grignon%'
AND location_type = '1'

LIMIT 15;