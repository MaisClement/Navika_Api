<?php

namespace App\Command;

use Symfony\Component\HttpClient\HttpClient;

class CommandFunctions
{
    public static function getGTFSDataFromApi($gtfs)
    {
        $url = 'https://transport.data.gouv.fr/api/datasets/' . $gtfs->getUrl();

        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();

        if ($status != 200) {
            return;
        }

        $content = $response->getContent();
        $results = json_decode($content);

        foreach ($results->history as $history) {
            if ($history->payload->format == 'GTFS') {
                return array(
                    'provider_id' => $gtfs->getId(),
                    'slug' => $results->aom->name,
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

    public static function getStatusFromActivePeriods($activePeriods)
    {
        $currentTime = time();
        $isFuture = false;
        $isPast = false;

        foreach($activePeriods as $periods) {
            $start =    (int)   $periods->start;
            $end =      (int)   $periods->end;

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
        if ($isFuture == true) {
            return 'past';
        }
    }
}