<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class NewsHelper
{
    public const NEWS_TYPE_STORY         = 'Story';
    public const NEWS_TYPE_VIDEO         = 'Video';
    public const NEWS_TYPE_IMAGE_GALLERY = 'Image Gallery';

    public static function newsTypes(): Collection
    {
        return SystemHelper::toOptions([
            self::NEWS_TYPE_STORY,
            self::NEWS_TYPE_VIDEO,
            self::NEWS_TYPE_IMAGE_GALLERY,
        ]);
    }
}
