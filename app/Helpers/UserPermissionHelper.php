<?php
namespace App\Helpers;

use Illuminate\Support\Collection;

class UserPermissionHelper
{
    private const SEPARATOR = ': ';

    public const ACCESS_VIEW_ANY     = 'View any';
    public const ACCESS_VIEW         = 'View';
    public const ACCESS_CREATE       = 'Create';
    public const ACCESS_UPDATE       = 'Update';
    public const ACCESS_DELETE       = 'Delete';
    public const ACCESS_RESTORE      = 'Restore';
    public const ACCESS_FORCE_DELETE = 'Force delete';

    public const MODULE_BREAKING_NEWS  = 'Breaking news';
    public const MODULE_CATEGORY       = 'Category';
    public const MODULE_CONTRIBUTOR    = 'Contributor';
    public const MODULE_EVENT          = 'Event';
    public const MODULE_GOOGLE_ADSENCE = 'Google adsence';
    public const MODULE_LOCATION       = 'Location';
    public const MODULE_MENU           = 'Menu';
    public const MODULE_MENU_ITEM      = 'Menu item';
    public const MODULE_NEWS           = 'News';
    public const MODULE_PAGE           = 'Page';
    public const MODULE_TAG            = 'Tag';
    public const MODULE_THEME          = 'Theme';
    public const MODULE_TREND          = 'Trend';
    public const MODULE_USER           = 'User';
    public const MODULE_ACTIVITY_LOG = 'Activity log';


    public static function modules(): Collection
    {
        return SystemHelper::toOptions([
            self::MODULE_BREAKING_NEWS,
            self::MODULE_CATEGORY,
            self::MODULE_CONTRIBUTOR,
            self::MODULE_EVENT,
            self::MODULE_GOOGLE_ADSENCE,
            self::MODULE_LOCATION,
            self::MODULE_MENU,
            self::MODULE_MENU_ITEM,
            self::MODULE_NEWS,
            self::MODULE_PAGE,
            self::MODULE_TAG,
            self::MODULE_THEME,
            self::MODULE_TREND,
            self::MODULE_USER,
            self::MODULE_ACTIVITY_LOG,
        ]);
    }

    public static function modulesPermissions(string $moduleName = self::MODULE_BREAKING_NEWS): Collection
    {
        $fullPermissionModules = [
            self::MODULE_BREAKING_NEWS,
            self::MODULE_NEWS,
            self::MODULE_PAGE,
            self::MODULE_USER,
        ];

        if (in_array($moduleName, $fullPermissionModules, true)) {
            return SystemHelper::toOptions([
                self::ACCESS_VIEW_ANY,
                self::ACCESS_VIEW,
                self::ACCESS_CREATE,
                self::ACCESS_UPDATE,
                self::ACCESS_DELETE,
                self::ACCESS_RESTORE,
                self::ACCESS_FORCE_DELETE,
            ]);
        }

        if ($moduleName == self::MODULE_THEME) {
            return SystemHelper::toOptions([
                self::ACCESS_VIEW_ANY,
                self::ACCESS_VIEW,
                self::ACCESS_UPDATE,
            ]);
        }

        if ($moduleName == self::MODULE_ACTIVITY_LOG) {
            return SystemHelper::toOptions([
                self::ACCESS_VIEW_ANY,
            ]);
        }

        return SystemHelper::toOptions([
            self::ACCESS_VIEW_ANY,
            self::ACCESS_VIEW,
            self::ACCESS_CREATE,
            self::ACCESS_UPDATE,
            self::ACCESS_DELETE,
        ]);
    }

    public static function modulePermissingNameGenerates(string $moduleName, string $accessName): string
    {
        return $moduleName . self::SEPARATOR . $accessName;
    }

}
