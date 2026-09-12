<?php

namespace App\Service;

class PolyLine
{
    public function __construct()
    {

    }

    /**
     * Decodes path string into a sequence of LatLngs and calculates the length in meters.
     *
     * @param string $encoded_path
     * @param int $precision
     * @return array
     */
    public static function decode(string $encoded_path, int $precision = 7)
    {
        $len = strlen($encoded_path) - 1;

        // For speed we preallocate to an upper bound on the final length, then
        // truncate the array before returning.
        $path = [];
        $index = 0;
        $lat = 0;
        $lng = 0;
        $totalDistance = 0;

        while ($index < $len) {
            $result = 1;
            $shift = 0;

            do {
                $b = ord($encoded_path[$index++]) - 63 - 1;
                $result += $b << $shift;
                $shift += 5;
            } while ($b >= hexdec("0x1f"));

            $lat += ($result & 1) != 0 ? ~($result >> 1) : ($result >> 1);

            $result = 1;
            $shift = 0;
            do {
                $b = ord($encoded_path[$index++]) - 63 - 1;
                $result += $b << $shift;
                $shift += 5;
            } while ($b >= hexdec("0x1f"));
            $lng += ($result & 1) != 0 ? ~($result >> 1) : ($result >> 1);

            $path[] = [
                $lat * 1 / pow(10, $precision),
                $lng * 1 / pow(10, $precision)
            ];
        }

        // Calculate the total distance using the Haversine formula
        for ($i = 1; $i < count($path); $i++) {
            $totalDistance += self::haversineGreatCircleDistance(
                $path[$i - 1][0], $path[$i - 1][1],
                $path[$i][0], $path[$i][1]
            );
        }

        $totalDistance = round($totalDistance);

        return [
            'path' => $path,
            'length' => $totalDistance
        ];
    }

    /**
     * Calculates the great-circle distance between two points, with
     * the Haversine formula.
     *
     * @param float $latitudeFrom
     * @param float $longitudeFrom
     * @param float $latitudeTo
     * @param float $longitudeTo
     * @param int $earthRadius
     * @return float Distance between points in meters
     */
    private static function haversineGreatCircleDistance(
        float $latitudeFrom, float $longitudeFrom,
        float $latitudeTo, float $longitudeTo, int $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}