<?php
namespace App\Helpers;

use Illuminate\Support\Collection;

class GoogleAdsenceHelper
{
    public const TYPE_SECTION = "Section";
    public const TYPE_SIDEBAR = "Sidebar";

    public const POSITION_TOP     = "Top";
    public const POSITION_BETWEEN = "Between";
    public const POSITION_BOTTOM  = "Bottom";

    public static function types(): Collection
    {
        return SystemHelper::toOptions([
            self::TYPE_SECTION,
            self::TYPE_SIDEBAR,
        ]);
    }

    public static function positions(): Collection
    {
        return SystemHelper::toOptions([
            self::POSITION_TOP,
            self::POSITION_BETWEEN,
            self::POSITION_BOTTOM,
        ]);
    }
}
