<?php
namespace App\Helpers;

class NewsHelper
{
    public const PAGE_SECTION_LEAD_NEWS    = 'Lead News';
    public const PAGE_SECTION_SPACIAL_NEWS = 'Spacial News';

    public static function pageSections()
    {
        return collect([
            (object) ['id' => self::PAGE_SECTION_LEAD_NEWS, 'name' => 'Lead News'],
            (object) ['id' => self::PAGE_SECTION_SPACIAL_NEWS, 'name' => 'Spacial News'],
        ]);
    }
}
