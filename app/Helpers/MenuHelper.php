<?php
namespace App\Helpers;

use Illuminate\Support\Collection;

class MenuHelper
{
    public const TYPE_HEADER = 'Header';
    public const TYPE_TOPBAR = 'Topbar';
    public const TYPE_OFFCANVAS = 'Offcanvas';
    public const TYPE_FOOTER = 'Footer';

    public const ITEM_MODEL_CATEGORY = 'Category';
    public const ITEM_MODEL_TAG      = 'Tag';

    public static function menuTypes(): Collection
    {
        return collect([
            (object) ['id' => self::TYPE_HEADER, 'name' => 'Header'],
            (object) ['id' => self::TYPE_TOPBAR, 'name' => 'Top bar'],
            (object) ['id' => self::TYPE_OFFCANVAS, 'name' => 'Offcanvas'],
            (object) ['id' => self::TYPE_FOOTER, 'name' => 'Footer'],
        ]);
    }

    public static function menuItemModels(): Collection
    {
        return collect([
            (object) ['id' => self::ITEM_MODEL_CATEGORY, 'name' => 'Category'],
            (object) ['id' => self::ITEM_MODEL_TAG, 'name' => 'Tag'],
        ]);
    }
}
