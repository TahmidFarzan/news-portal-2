<?php
namespace App\Helpers;

use Illuminate\Support\Collection;

class MenuHelper
{
    public const MENU_TYPE_HEADER    = 'Header';
    public const MENU_TYPE_TOPBAR    = 'Topbar';
    public const MENU_TYPE_OFFCANVAS = 'Offcanvas';
    public const MENU_TYPE_FOOTER    = 'Footer';

    public const MENU_ITEM_MODEL_CATEGORY = 'Category';
    public const MENU_ITEM_MODEL_TAG      = 'Tag';

    public static function menuTypes(): Collection
    {
        return collect([
            (object) ['id' => self::MENU_TYPE_HEADER, self::MENU_TYPE_HEADER],
            (object) ['id' => self::MENU_TYPE_TOPBAR, 'name' => self::MENU_TYPE_TOPBAR],
            (object) ['id' => self::MENU_TYPE_OFFCANVAS, 'name' => self::MENU_TYPE_OFFCANVAS],
            (object) ['id' => self::MENU_TYPE_FOOTER, 'name' => self::MENU_TYPE_FOOTER],
        ]);
    }

    public static function menuItemModels(): Collection
    {
        return collect([
            (object) ['id' => self::MENU_ITEM_MODEL_CATEGORY, 'name' => self::MENU_ITEM_MODEL_CATEGORY],
            (object) ['id' => self::MENU_ITEM_MODEL_TAG, 'name' => self::MENU_ITEM_MODEL_TAG],
        ]);
    }
}
