<?php

namespace App\Command;

use Symfony\Component\HttpClient\HttpClient;

class CommandFunctions
{
    public static function getGTFSDataFromApi($gtfs): array
    {
        $url = 'https://transport.data.gouv.fr/api/datasets/' . $gtfs->getUrl();

        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200) {
            return [];
        }

        $content = $response->getContent();
        $results = json_decode($content);

        foreach ($results->history as $history) {
            if ($history->payload->format == 'GTFS') {
                return array(
                    'provider_id' => $gtfs->getId(),
                    'slug' => $results->publisher->name,
                    'title' => $gtfs->getName(),
                    'type' => $history->payload->format,
                    'url' => $history->payload->resource_url,
                    'filenames' => $history->payload->filenames,
                    'updated' => date('Y-m-d H:i:s', strtotime($history->updated_at)),
                    'flag' => 0,
                );
            }
        }

        return [];
    }

    public static function getStatusFromActivePeriods($activePeriods): string
    {
        $currentTime = time();
        $isFuture = false;
        $isPast = false;

        foreach ($activePeriods as $periods) {
            $start = (int) $periods->start;
            $end = (int) $periods->end;

            if ($currentTime < $start) {
                $isFuture = true;
            } elseif ($currentTime > $end) {
                $isPast = true;
            } else {
                return 'active';
            }
        }

        if ($isFuture == true) {
            return 'future';
        }
        if ($isPast == true) {
            return 'past';
        }

        return 'active';
    }

    public static function remove_directory(string $dir) {
        if (is_dir($dir)) { 
            $objects = scandir($dir);
            foreach ($objects as $object) { 
              if ($object != "." && $object != "..") { 
                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                    CommandFunctions::remove_directory($dir. DIRECTORY_SEPARATOR .$object);
                else
                  unlink($dir. DIRECTORY_SEPARATOR .$object); 
              } 
            }
            rmdir($dir); 
          } 
    }
    
    public static function recursive_copy($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    CommandFunctions::recursive_copy($src .'/'. $file, $dst .'/'. $file);
                }
                else {
                    copy($src .'/'. $file,$dst .'/'. $file);
                }
            }
        }
        closedir($dir);
    }
}