<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class EventHelper
{
    public const POSITION_TOP         = 'Top';
    public const POSITION_BOTTOM         = 'Bottom';

    public static function positions(): Collection
    {
        return SystemHelper::toOptions([
            self::POSITION_TOP,
            self::POSITION_BOTTOM,
        ]);
    }
}
