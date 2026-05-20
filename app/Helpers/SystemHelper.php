<?php
namespace App\Helpers;

use Illuminate\Support\Collection;

class SystemHelper
{
    public const LANGUAGE_DEFAULT_CODE  = "en";
    public const LANGUAGE_EXTRA_BN_CODE = "bn";

    private const PER_PAGE_10  = "10";
    private const PER_PAGE_25  = "25";
    private const PER_PAGE_50  = "50";
    private const PER_PAGE_100 = "100";

    private const ACTIVITY_LOG_CREATED  = 'Created';
    private const ACTIVITY_LOG_UPDATED  = 'Updated';
    private const ACTIVITY_LOG_DELETED  = 'Deleted';
    private const ACTIVITY_LOG_TRASHED  = 'Trashed';
    private const ACTIVITY_LOG_RESTORED = 'Restored';

    private const ACTIVITY_LOG_SUBJECT_USER = 'User';

    public const MENU_TYPE_HEADER = 'Header';
    public const MENU_TYPE_TOPBAR = 'Top bar';
    public const MENU_TYPE_OFFCANVAS = 'Off Canvas';
    public const MENU_TYPE_FOOTER = 'Footer';

    public const MENU_ITEM_MODEL_CATEGORY = 'Category';
    public const MENU_ITEM_MODEL_TAG      = 'Tag';

    public static function perPages(): Collection
    {
        return collect([
            (object) ['id' => self::PER_PAGE_10, 'name' => '10'],
            (object) ['id' => self::PER_PAGE_25, 'name' => '25'],
            (object) ['id' => self::PER_PAGE_50, 'name' => '50'],
            (object) ['id' => self::PER_PAGE_100, 'name' => '100'],
        ]);
    }

    public static function activityLogEvents(): Collection
    {
        return collect([
            (object) ['id' => self::ACTIVITY_LOG_CREATED, 'name' => 'Created'],
            (object) ['id' => self::ACTIVITY_LOG_UPDATED, 'name' => 'Updated'],
            (object) ['id' => self::ACTIVITY_LOG_DELETED, 'name' => 'Deleted'],
            (object) ['id' => self::ACTIVITY_LOG_TRASHED, 'name' => 'Trashed'],
            (object) ['id' => self::ACTIVITY_LOG_RESTORED, 'name' => 'Restored'],
        ]);
    }

    public static function activityLogSubjectTypes(): Collection
    {
        return collect([
            (object) ['id' => self::ACTIVITY_LOG_SUBJECT_USER, 'name' => 'User'],
        ]);
    }

    public static function menuTypes(): Collection
    {
        return collect([
            (object) ['id' => self::MENU_TYPE_HEADER, 'name' => 'Header'],
            (object) ['id' => self::MENU_TYPE_TOPBAR, 'name' => 'Top bar'],
            (object) ['id' => self::MENU_TYPE_OFFCANVAS, 'name' => 'Off Canvas'],
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
