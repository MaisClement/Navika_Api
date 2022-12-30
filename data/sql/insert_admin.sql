INSERT INTO lignes
VALUES
('SNCF', 'SNCF', 'Trains SNCF', 'SNCF', '2023-01-01T02:00:00+02:00', '' , 'nationalrail', 'nationalrail', '', 800, 'SNCF', '', 'SNCF', 'aaaaaa', '000000', '0 0 0 1', '000000', '', '', '', '', 'Trains SNCF', '', '', '', 'active'),
('TER',  'TER',  'TER',         'TER',  '2023-01-01T02:00:00+02:00', '' , 'rail', 'rail', '', 800, 'SNCF', '', 'SNCF', 'aaaaaa', '000000', '0 0 0 1', '000000', '', '', '', '', 'TER'        , '', '', '', 'active');



INSERT INTO arrets_lignes
(id,     route_long_name, stop_id,    stop_name,                          stop_lon,              stop_lat,              operatorname, pointgeo,                           nom_commune,                        code_insee)
VALUES
("SNCF", "Trains SNCF", "ADMIN:0101", "Château-Thierry"                 , "3.4094551756509444" , "49.03803714166139"  , "SNCF", "3.4094551756509444,49.03803714166139"  , "Château-Thierry"                 , "02168"),
("SNCF", "Trains SNCF", "ADMIN:0102", "Crépy-en-Valois"                 , "2.8876260684564095" , "49.231226048139426" , "SNCF", "2.8876260684564095,49.231226048139426" , "Crépy-en-Valois"                 , "60176"),
("SNCF", "Trains SNCF", "ADMIN:0103", "Creil"                           , "2.468932722996438"  , "49.2638259762795"   , "SNCF", "2.468932722996438,49.2638259762795"    , "Creil"                           , "60175"),
("SNCF", "Trains SNCF", "ADMIN:0104", "Montargis"                       , "2.7429394830349696" , "48.00672778886036"  , "SNCF", "2.7429394830349696,48.00672778886036"  , "Montargis"                       , "45208"),
("SNCF", "Trains SNCF", "ADMIN:0105", "Malesherbes"                     , "2.4012858739096004" , "48.293634553791804" , "SNCF", "2.4012858739096004,48.293634553791804" , "Malesherbes"                     , "45191"),
("SNCF", "Trains SNCF", "ADMIN:0106", "Dreux"                           , "1.3710627915334217" , "48.73157378987803"  , "SNCF", "1.3710627915334217,48.73157378987803"  , "Dreux"                           , "28134"),
("SNCF", "Trains SNCF", "ADMIN:0107", "Juvisy"                          , "2.38418315891281"   , "48.68960585601327"  , "SNCF", "2.38418315891281,48.68960585601327"    , "Juvisy"                          , "91326"),
("SNCF", "Trains SNCF", "ADMIN:0108", "Etampes"                         , "2.159956601313322"  , "48.4369213474364"   , "SNCF", "2.159956601313322,48.4369213474364"    , "Etampes"                         , "91223"),
("SNCF", "Trains SNCF", "ADMIN:0109", "Dourdan"                         , "2.008393202332081"  , "48.533919414085894" , "SNCF", "2.008393202332081,48.533919414085894"  , "Dourdan"                         , "91200"),
("SNCF", "Trains SNCF", "ADMIN:0110", "Rambouillet"                     , "1.8321617680989135" , "48.643467276907465" , "SNCF", "1.8321617680989135,48.643467276907465" , "Rambouillet"                     , "78517"),
("SNCF", "Trains SNCF", "ADMIN:0111", "Montereau"                       , "2.942946687501169"  , "48.37997743815116"  , "SNCF", "2.942946687501169,48.37997743815116"   , "Montereau"                       , "45213"),
("SNCF", "Trains SNCF", "ADMIN:0112", "Melun"                           , "2.654837989014943"  , "48.52788300025775"  , "SNCF", "2.654837989014943,48.52788300025775"   , "Melun"                           , "77288"),
("SNCF", "Trains SNCF", "ADMIN:0113", "Massy - Palaiseau"               , "2.258703107078832"  , "48.72480359870355"  , "SNCF", "2.258703107078832,48.72480359870355"   , "Massy - Palaiseau"               , "91377"),
("SNCF", "Trains SNCF", "ADMIN:0114", "Versailles Chantiers"            , "2.1354700907472384" , "48.79583621479475"  , "SNCF", "2.1354700907472384,48.79583621479475"  , "Versailles Chantiers"            , "78646"),
("SNCF", "Trains SNCF", "ADMIN:0115", "Mantes-la-Jolie"                 , "1.7033904939259679" , "48.989035968118294" , "SNCF", "1.7033904939259679,48.989035968118294" , "Mantes-la-Jolie"                 , "78361"),
("SNCF", "Trains SNCF", "ADMIN:0116", "Persan - Beaumont"               , "2.278474737910587"  , "49.147663878395456" , "SNCF", "2.278474737910587,49.147663878395456"  , "Persan - Beaumont"               , "95052"),
("SNCF", "Trains SNCF", "ADMIN:0117", "Marne-la-Vallée Chessy"          , "2.78336655061271"   , "48.86868601691091"  , "SNCF", "2.78336655061271,48.86868601691091"    , "Marne-la-Vallée Chessy"          , "77111"),
("SNCF", "Trains SNCF", "ADMIN:0118", "Gare d'Austerlitz"               , "2.3667244975569255" , "48.8423536391491"   , "SNCF", "2.3667244975569255,48.8423536391491"   , "Gare d'Austerlitz"               , "75056"),
("SNCF", "Trains SNCF", "ADMIN:0119", "Gare Montparnasse"               , "2.319342991047202"  , "48.84067243107856"  , "SNCF", "2.319342991047202,48.84067243107856"   , "Gare Montparnasse"               , "75056"),
("SNCF", "Trains SNCF", "ADMIN:0121", "Gare de l'Est"                   , "2.3586128821358923" , "48.87703379100105"  , "SNCF", "2.3586128821358923,48.87703379100105"  , "Gare de l'Est"                   , "75056"),
("SNCF", "Trains SNCF", "ADMIN:0122", "Gare Saint-Lazare"               , "2.325269793819657"  , "48.875942171384125" , "SNCF", "2.325269793819657,48.875942171384125"  , "Gare Saint-Lazare"               , "75056"),
("SNCF", "Trains SNCF", "ADMIN:0123", "Gare du Nord"                    , "2.3568259434290013" , "48.88096281962588"  , "SNCF", "2.3568259434290013,48.88096281962588"  , "Gare du Nord"                    , "75056"),
("SNCF", "Trains SNCF", "ADMIN:0124", "Gare de Lyon"                    , "2.375147828585711"  , "48.844398547339175" , "SNCF", "2.375147828585711,48.844398547339175"  , "Gare de Lyon"                    , "75056"),
("SNCF", "Trains SNCF", "ADMIN:0125", "Aéroport CDG - Terminal 2 (TGV)" , "2.5722073332859"    , "49.00448713286739"  , "SNCF", "2.5722073332859,49.00448713286739"     , "Aéroport CDG - Terminal 2 (TGV)" , "77291"),
("SNCF", "Trains SNCF", "ADMIN:0126", "Gisors"                          , "1.7847635457732476" , "49.28540169586443"  , "SNCF", "1.7847635457732476,49.28540169586443"  , "Gisors"                          , "27284"),
("SNCF", "Trains SNCF", "ADMIN:0127", "Longueville"                     , "3.2502137599421386" , "48.513394611324955" , "SNCF", "3.2502137599421386,48.513394611324955" , "Longueville"                     , "77260"),
("SNCF", "Trains SNCF", "ADMIN:0128", "La Ferté-sous-Jouarre"           , "3.1246701061786717" , "48.95062899573038"  , "SNCF", "3.1246701061786717,48.95062899573038"  , "La Ferté-sous-Jouarre"           , "77183"),



INSERT INTO stops
(stop_id,     stop_code, stop_name,                    stop_desc, stop_lon,       stop_lat,              zone_id, stop_url, location_type, parent_station, wheelchair_boarding, stop_timezone, level_id, platform_code)
VALUES
("ADMIN:0101", "", "Château-Thierry"                 , "", "3.4094551756509444" , "49.03803714166139"  , "0", "", "0", "IDFM:411379" , "", "", "0", ""),
("ADMIN:0102", "", "Crépy-en-Valois"                 , "", "2.8876260684564095" , "49.231226048139426" , "0", "", "0", "IDFM:411397" , "", "", "0", ""),
("ADMIN:0103", "", "Creil"                           , "", "2.468932722996438"  , "49.2638259762795"   , "0", "", "0", "IDFM:411441" , "", "", "0", ""),
("ADMIN:0104", "", "Montargis"                       , "", "2.7429394830349696" , "48.00672778886036"  , "0", "", "0", "IDFM:411483" , "", "", "0", ""),
("ADMIN:0105", "", "Malesherbes"                     , "", "2.4012858739096004" , "48.293634553791804" , "0", "", "0", "IDFM:411486" , "", "", "0", ""),
("ADMIN:0106", "", "Dreux"                           , "", "1.3710627915334217" , "48.73157378987803"  , "0", "", "0", "IDFM:411493" , "", "", "0", ""),
("ADMIN:0107", "", "Juvisy"                          , "", "2.38418315891281"   , "48.68960585601327"  , "0", "", "0", "IDFM:478505" , "", "", "0", ""),
("ADMIN:0108", "", "Etampes"                         , "", "2.159956601313322"  , "48.4369213474364"   , "0", "", "0", "IDFM:478855" , "", "", "0", ""),
("ADMIN:0109", "", "Dourdan"                         , "", "2.008393202332081"  , "48.533919414085894" , "0", "", "0", "IDFM:59836"  , "", "", "0", ""),
("ADMIN:0110", "", "Rambouillet"                     , "", "1.8321617680989135" , "48.643467276907465" , "0", "", "0", "IDFM:60665"  , "", "", "0", ""),
("ADMIN:0111", "", "Montereau"                       , "", "2.942946687501169"  , "48.37997743815116"  , "0", "", "0", "IDFM:61414"  , "", "", "0", ""),
("ADMIN:0112", "", "Melun"                           , "", "2.654837989014943"  , "48.52788300025775"  , "0", "", "0", "IDFM:61926"  , "", "", "0", ""),
("ADMIN:0113", "", "Massy - Palaiseau"               , "", "2.258703107078832"  , "48.72480359870355"  , "0", "", "0", "IDFM:63244"  , "", "", "0", ""),
("ADMIN:0114", "", "Versailles Chantiers"            , "", "2.1354700907472384" , "48.79583621479475"  , "0", "", "0", "IDFM:63880"  , "", "", "0", ""),
("ADMIN:0115", "", "Mantes-la-Jolie"                 , "", "1.7033904939259679" , "48.989035968118294" , "0", "", "0", "IDFM:65931"  , "", "", "0", ""),
("ADMIN:0116", "", "Persan - Beaumont"               , "", "2.278474737910587"  , "49.147663878395456" , "0", "", "0", "IDFM:67292"  , "", "", "0", ""),
("ADMIN:0117", "", "Marne-la-Vallée Chessy"          , "", "2.78336655061271"   , "48.86868601691091"  , "0", "", "0", "IDFM:68385"  , "", "", "0", ""),
("ADMIN:0118", "", "Gare d'Austerlitz"               , "", "2.3667244975569255" , "48.8423536391491"   , "0", "", "0", "IDFM:71135"  , "", "", "0", ""),
("ADMIN:0119", "", "Gare Montparnasse"               , "", "2.319342991047202"  , "48.84067243107856"  , "0", "", "0", "IDFM:71139"  , "", "", "0", ""),
("ADMIN:0121", "", "Gare de l'Est"                   , "", "2.3586128821358923" , "48.87703379100105"  , "0", "", "0", "IDFM:71359"  , "", "", "0", ""),
("ADMIN:0122", "", "Gare Saint-Lazare"               , "", "2.325269793819657"  , "48.875942171384125" , "0", "", "0", "IDFM:71370"  , "", "", "0", ""),
("ADMIN:0123", "", "Gare du Nord"                    , "", "2.3568259434290013" , "48.88096281962588"  , "0", "", "0", "IDFM:71410"  , "", "", "0", ""),
("ADMIN:0124", "", "Gare de Lyon"                    , "", "2.375147828585711"  , "48.844398547339175" , "0", "", "0", "IDFM:73626"  , "", "", "0", ""),
("ADMIN:0125", "", "Aéroport CDG - Terminal 2 (TGV)" , "", "2.5722073332859"    , "49.00448713286739"  , "0", "", "0", "IDFM:73699"  , "", "", "0", ""),
("ADMIN:0126", "", "Gisors"                          , "", "1.7847635457732476" , "49.28540169586443"  , "0", "", "0", "IDFM:74348"  , "", "", "0", ""),
("ADMIN:0127", "", "Longueville"                     , "", "3.2502137599421386" , "48.513394611324955" , "0", "", "0", "IDFM:61858"  , "", "", "0", ""),
("ADMIN:0128", "", "La Ferté-sous-Jouarre"           , "", "3.1246701061786717" , "48.95062899573038"  , "0", "", "0", "IDFM:68918"  , "", "", "0", ""),
