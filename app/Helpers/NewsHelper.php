<?php
namespace App\Helpers;

class NewsHelper
{
    public const NEWS_TYPE_STORY         = 'Story';
    public const NEWS_TYPE_VIDEO         = 'Video';
    public const NEWS_TYPE_PHOTO_GALLERY = 'Photo Gallery';

    public const PAGE_SECTION_LEAD_NEWS    = 'Lead News';
    public const PAGE_SECTION_SPACIAL_NEWS = 'Spacial News';

    public static function pageSections()
    {
        return collect([
            (object) ['id' => self::PAGE_SECTION_LEAD_NEWS, 'name' => 'Lead News'],
            (object) ['id' => self::PAGE_SECTION_SPACIAL_NEWS, 'name' => 'Spacial News'],
        ]);
    }

    public static function newsTypes()
    {
        return collect([
            (object) ['id' => self::NEWS_TYPE_STORY, 'name' => 'Story'],
            (object) ['id' => self::NEWS_TYPE_VIDEO, 'name' => 'Video'],
            (object) ['id' => self::NEWS_TYPE_PHOTO_GALLERY, 'name' => 'Photo Gallery'],
        ]);
    }
}
