<?php
namespace App\Helpers;

use Illuminate\Support\Collection;

class MenuHelper
{
    public const MENU_TYPE_HEADER = 'Header';
    public const MENU_TYPE_TOPBAR = 'Topbar';
    public const MENU_TYPE_OFFCANVAS = 'Offcanvas';
    public const MENU_TYPE_FOOTER = 'Footer';

    public const MENU_ITEM_MODEL_CATEGORY = 'Category';
    public const MENU_ITEM_MODEL_TAG      = 'Tag';

    public static function menuTypes(): Collection
    {
        return collect([
            (object) ['id' => self::MENU_TYPE_HEADER, 'name' => 'Header'],
            (object) ['id' => self::MENU_TYPE_TOPBAR, 'name' => 'Topbar'],
            (object) ['id' => self::MENU_TYPE_OFFCANVAS, 'name' => 'Offcanvas'],
            (object) ['id' => self::MENU_TYPE_FOOTER, 'name' => 'Footer'],
        ]);
    }

    public static function menuItemModels(): Collection
    {
        return collect([
            (object) ['id' => self::MENU_ITEM_MODEL_CATEGORY, 'name' => 'Category'],
            (object) ['id' => self::MENU_ITEM_MODEL_TAG, 'name' => 'Tag'],
        ]);
    }
}
