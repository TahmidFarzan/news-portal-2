<?php

namespace App\Helpers;

class TagifyHelper {
    public static function dataFormat($data) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!is_array($data)) {
            return [];
        }

        if (is_string(reset($data))) {
            return $data;
        }

        if (isset($data[0]['value'])) {
            return array_map(function ($item) {
                return $item['value'];
            }, $data);
        }

        return [];
    }

    public static function dataFormatToString($data) {
        return implode(', ', $data);
    }

    public static function dataStringFormatFull($data) {
        return self::dataFormatToString(self::dataFormat($data));
    }
}
