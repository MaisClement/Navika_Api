---------------------
-- Search places
SELECT S.stop_id, S.stop_code, S.stop_name, S.stop_lat, S.stop_lon, point(S.stop_lat, S.stop_lon) AS geo_point,
ST_Distance_Sphere(
    point(S.stop_lat, S.stop_lon),
    point(?, ?)
) AS distance, S.stop_desc, S.zone_id, S.location_type, S.level_id, S.platform_code, 
CONCAT((
    SELECT CONCAT(Z.zip_code,'; ', T.town_name)
    FROM town T
    LEFT JOIN zip_code Z
    ON T.town_id = Z.town_id
    WHERE ST_CONTAINS(T.town_polygon, point(S.stop_lat, S.stop_lon))
    LIMIT 1
)) as town
FROM stops S

WHERE (ST_Distance_Sphere(
    point(S.stop_lat, S.stop_lon),
    point(?, ?)
)) < ?
AND location_type = ?

ORDER BY distance ASC

---------------------
-- 
SELECT S2.stop_id, S2.stop_code, S2.stop_name, S2.stop_lat, S2.stop_lon, S2.zone_id, A.nom_commune AS town, A.code_insee AS zip_code
FROM arrets_lignes A

INNER JOIN stops S
ON A.stop_id = S.stop_id

INNER JOIN stops S2
ON S.parent_station = S2.stop_id

WHERE LOWER( A.stop_name ) LIKE ?

UNION DISTINCT

SELECT S2.stop_id, S2.stop_code, S2.stop_name, S2.stop_lat, S2.stop_lon, S2.zone_id, A.nom_commune AS town, A.code_insee AS zip_code
FROM arrets_lignes A

INNER JOIN stops S
ON A.stop_id = S.stop_id

INNER JOIN stops S2
ON S.parent_station = S2.stop_id

WHERE LOWER ( A.nom_commune ) LIKE ?

LIMIT 15

GROUP BY S2.stop_id;

---------------------
-- Select lines at stop
SELECT L.*

FROM stops S
INNER JOIN arrets_lignes A
ON S.stop_id = A.stop_id 
INNER JOIN lignes L
ON REPLACE(A.id, 'IDFM:', '') = L.id_line

WHERE parent_station = 'IDFM:71545';

---------------------
-- Select lines at stop
SELECT stop_name
FROM stops
WHERE stop_id = 'IDFM:5829' OR parent_station = 'IDFM:5829';

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
