<?php

$fichier = '../data/cache/actualites.json';

$url = 'https://api-iv.iledefrance-mobilites.fr/banners';

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
foreach ($results as $result) {

    $url = $result->link;
    if (strpos($url, 'iledefrance-mobilites.fr') == false) {
        $url = 'https://me-deplacer.iledefrance-mobilites.fr' . $url;
    }

    $messages[] = array(
        "id"            =>  (string)    $result->id,
        "status"        =>  (string)    "active",
        "cause"         =>  (string)    "",
        "category"      =>  (string)    "",
        "severity"      =>  (int)       $severity_i[$result->type],
        "effect"        =>  (string)    "OTHER",
        "updated_at"    =>  (string)    $result->updatedDate,
        "message"       =>  array(
            "title"     =>      $result->title,
            "text"      =>      $result->description,
            "button"      =>    $result->buttonText,
            "link"      =>      $url,
        ),
    );
}

$json = array(
    "api"         => array(
        "current_version"      =>  (float)       0.1,
        "lastest_version"      =>  (float)       0.1,
        "oldest_version"       =>  (float)       0.1,
        "support"              =>  (string)       "active",
    ),
    "app"         => array(
        "current_version"      =>  (float)       0.1,
        "lastest_version"      =>  (float)       0.1,
        "oldest_version"       =>  (float)       0.1,
        "support"              =>  (string)       "active",
    ),
    "message"     => $messages,

);

echo json_encode($json);
