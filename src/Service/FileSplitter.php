<?php

namespace App\Service;

class FileSplitter
{
    public static function exec($dir, $provider, $type)
    {
        $file = $dir . '/' . $provider . '/' . $type . '.txt';

        $csvData = file($file, FILE_IGNORE_NEW_LINES);
        $linesPerFile = 30000;

        $header = array_shift($csvData);

        $splitCount = 1;
        $currentSplit = [];

        foreach ($csvData as $line) {
            $currentSplit[] = $line;

            if (count($currentSplit) === $linesPerFile) {
                self::writeSplitFile($file, $type, $splitCount, $header, $currentSplit);
                $currentSplit = [];
                $splitCount++;
            }
        }

        if (!empty($currentSplit)) {
            self::writeSplitFile($file, $type, $splitCount, $header, $currentSplit);
        }

        return $splitCount;
    }

    public static function writeSplitFile($originalFilePath, $type, $splitNumber, $header, $data)
    {
        $splitFilePath = pathinfo($originalFilePath, PATHINFO_DIRNAME) . '/' . $type . '_' . $splitNumber . '.txt';
        file_put_contents($splitFilePath, $header . PHP_EOL . implode(PHP_EOL, $data));
    }
}