<?php

namespace App\Service;

class PolylineDecoder
{
    public static function decode(string $encoded, int $precision = 6, int $distance = 0): array
    {
        $index = 0;
        $lat = 0;
        $lng = 0;
        $coordinates = [];

        $length = strlen($encoded);
        $factor = pow(10, $precision);

        while ($index < $length) {
            $lat += self::decodeValue($encoded, $index);
            $lng += self::decodeValue($encoded, $index);

            // GeoJSON = longitude, latitude
            $coordinates[] = [
                $lng / $factor,
                $lat / $factor,
            ];
        }

        return [
            'type' => 'LineString',
            'coordinates' => $coordinates,
            'properties' => [
                array(
                    'length' => $distance,
                ),
            ],
        ];
    }

    private static function decodeValue(string $encoded, int &$index): int
    {
        $result = 0;
        $shift = 0;

        do {
            $byte = ord($encoded[$index++]) - 63;

            $result |= ($byte & 0x1F) << $shift;
            $shift += 5;
        } while ($byte >= 0x20);

        return ($result & 1)
            ? ~(intdiv($result, 2))
            : intdiv($result, 2);
    }
}