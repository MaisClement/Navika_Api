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
            "severity"      =>  (int)       1,
            "effect"        =>  (String)    "OTHER",
            "updated_at"    =>  (String)    "20221029T105845",
            "message"       =>  array(
                "title"     =>      "Coucou",
                "text"      =>      "Pas content",
            ),
        )
    ],
    
);

echo json_encode($json);

?>