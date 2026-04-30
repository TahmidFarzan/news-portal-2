<?php
namespace App\Helpers;

class NewsHelper
{
    public const HOME_PAGE_SECTION_LEAD_NEWS     = 'Lead News';
    public const HOME_PAGE_SECTION_SPACIAL_NEWS     = 'Spacial News';

    public static function homePageSectionCategories()
    {
        return collect([
            (object) ['id' => self::HOME_PAGE_SECTION_LEAD_NEWS, 'name' => 'Lead News'],
            (object) ['id' => self::HOME_PAGE_SECTION_SPACIAL_NEWS, 'name' => 'Lead News'],
        ]);
    }
}
