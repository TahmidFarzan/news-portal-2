<?php
namespace App\Helpers;

class PageHelper
{
    public const PAGE_HOME     = 'Home';
    public const PAGE_CATEGORY = 'Category';

    public const PAGE_SECTION_LEAD_NEWS     = 'Lead News';
    public const PAGE_SECTION_CATEGORY_NEWS = 'Category News';

    public static function pages()
    {
        return collect([
            (object) ['id' => self::PAGE_HOME, 'name' => 'Home'],
            (object) ['id' => self::PAGE_CATEGORY, 'name' => 'Category'],
        ]);
    }

    public static function pageSections()
    {
        return collect([
            (object) ['id' => self::PAGE_SECTION_LEAD_NEWS, 'name' => 'Lead News'],
            (object) ['id' => self::PAGE_SECTION_CATEGORY_NEWS, 'name' => 'Category News'],
        ]);
    }

}
