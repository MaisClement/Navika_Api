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
-- Select lines at stop
SELECT L.*

FROM stops S
INNER JOIN arrets_lignes A
ON S.stop_id = A.stop_id 
INNER JOIN lignes L
ON REPLACE(A.id, 'IDFM:', '') = L.id_line

WHERE parent_station = 'IDFM:71545';

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
