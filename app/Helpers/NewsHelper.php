<?php
namespace App\Helpers;

class NewsHelper
{
    public const NEWS_TYPE_STORY         = 'Story';
    public const NEWS_TYPE_VIDEO         = 'Video';
    public const NEWS_TYPE_IMAGE_GALLERY = 'Image Gallery';

    public static function newsTypes()
    {
        return collect([
            (object) ['id' => self::NEWS_TYPE_STORY, 'name' => 'Story'],
            (object) ['id' => self::NEWS_TYPE_VIDEO, 'name' => 'Video'],
            (object) ['id' => self::NEWS_TYPE_IMAGE_GALLERY, 'name' => 'Image Gallery'],
        ]);
    }
}
