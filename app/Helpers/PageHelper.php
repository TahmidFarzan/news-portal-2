<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class PageHelper
{
    public const PAGE_HOME     = 'Home';
    public const PAGE_CATEGORY = 'Category';

    public const PAGE_SECTION_LEAD_NEWS     = 'Lead News';
    public const PAGE_SECTION_CATEGORY_NEWS = 'Category News';

    public static function pages(): Collection
    {
        return SystemHelper::toOptions([
            self::PAGE_HOME,
            self::PAGE_CATEGORY,
        ]);
    }

    public static function pageSections(): Collection
    {
        return SystemHelper::toOptions([
            self::PAGE_SECTION_LEAD_NEWS,
            self::PAGE_SECTION_CATEGORY_NEWS,
        ]);
    }
}
