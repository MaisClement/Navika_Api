<?php

$json = array("api"         => array(
        "current_version"      =>  (float)       0.1 ,
        "lastest_version"      =>  (float)       0.1 ,
        "oldest_version"       =>  (float)       0.1 ,
        "support"              =>  (String)       "active",
    ),
    "app"         => array(
        "current_version"      =>  (float)       0.1 ,
        "lastest_version"      =>  (float)       0.1 ,
        "oldest_version"       =>  (float)       0.1 ,
        "support"              =>  (String)       "active",
    ),
    "message"     => [
        array(
            "id"            =>  (String)    "message-01",
            "status"        =>  (String)    "active",
            "cause"         =>  (String)    "incidents",
            "category"      =>  (String)    "Incidents",
            "severity"      =>  (int)       3,
            "effect"        =>  (String)    "OTHER",
            "updated_at"    =>  (String)    "20221029T105845",
            "message"       =>  array(
                "title"     =>      "Travaux ce week-end",
                "text"      =>      "Des trains supprimés et stations fermées les samedis et le dimanches sur les lignes du réseau SNCF et RATP. Retrouvez le détail de ces travaux sur la période. ",
            ),
        ),
    ],
    
);

echo json_encode($json);

?>