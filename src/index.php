<?php

$fichier = '../data/cache/actualites.json';

$url = 'https://data.iledefrance-mobilites.fr/explore/dataset/actualites/download/?format=json&timezone=Europe/Berlin&lang=fr';

if (is_file($fichier) && filesize($fichier) > 5 && (time() - filemtime($fichier) < 60)) {
    $results = file_get_contents($fichier);

} else {
    $results = curl($url);
    file_put_contents($fichier, $results);
}

$results = json_decode($results);

// ------------

$severity_i = array(
    0 => 4,
    1 => 4,
    2 => 1,
);

$messages = [];
foreach($results as $result) {
    $messages[] = array(
        "id"            =>  (String)    $result->fields->id,
        "status"        =>  (String)    "active",
        "cause"         =>  (String)    "",
        "category"      =>  (String)    "",
        "severity"      =>  (int)       $severity_i[$result->fields->type],
        "effect"        =>  (String)    "OTHER",
        "updated_at"    =>  (String)    $result->fields->updateddate,
        "message"       =>  array(
            "title"     =>      $result->fields->title,
            "text"      =>      $result->fields->description,
            "button"      =>    $result->fields->buttontext,
            "link"      =>      $result->fields->link,
        ),
    );
}

$json = array("api"         => array(
        "current_version"      =>  (float)       0.1 ,
        "lastest_version"      =>  (float)       0.1 ,
        "oldest_version"       =>  (float)       0.1 ,
        "support"              =>  (String)      "active",
    ),
    "app"         => array(
        "current_version"      =>  (float)       0.1 ,
        "lastest_version"      =>  (float)       0.1 ,
        "oldest_version"       =>  (float)       0.1 ,
        "support"              =>  (String)      "active",
    ),
    "message"     => $messages,
    
);

echo json_encode($json);

?>