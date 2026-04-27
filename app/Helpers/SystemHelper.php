<?php
namespace App\Helpers;

class SystemHelper
{
    public const USER_ROLE_ADMIN     = 'Admin';
    public const USER_ROLE_NEWS_DESK = 'News Desk';

    public const NEWS_USER_TYPE_AUTHOR         = 'Author';
    public const NEWS_USER_TYPE_SPICIAL_AUTHOR = 'Spical Author';

    public const NEWS_USER_TYPE_EDITOR     = 'Editor';
    public const NEWS_USER_TYPE_SUB_EDITOR = 'Sub Editor';

    public static function perPages()
    {
        return collect([
            (object) ['id' => "10", 'name' => "10"],
            (object) ['id' => "25", 'name' => "25"],
            (object) ['id' => "50", 'name' => "50"],
            (object) ['id' => "100", 'name' => "100"],
        ]);
    }

    public static function genders()
    {
        return collect([
            (object) ['id' => 'Male', 'name' => 'Male'],
            (object) ['id' => 'Female', 'name' => 'Female'],
            (object) ['id' => 'Other', 'name' => 'Other'],
        ]);
    }

    public static function religions()
    {
        return collect([
            (object) ['id' => 'Islam', 'name' => 'Islam'],
            (object) ['id' => 'Hindu', 'name' => 'Hindu'],
            (object) ['id' => 'Christian', 'name' => 'Christian'],
            (object) ['id' => 'Other', 'name' => 'Other'],
        ]);
    }

    public static function maritalStatuses()
    {
        return collect([
            (object) ['id' => 'Single', 'name' => 'Single'],
            (object) ['id' => 'Married', 'name' => 'Married'],
            (object) ['id' => 'Divorce', 'name' => 'Divorce'],
            (object) ['id' => 'Separated', 'name' => 'Separated'],
            (object) ['id' => 'Other', 'name' => 'Other'],
        ]);
    }

    public static function activityLogEvents()
    {
        return collect([
            (object) ['id' => 'Created', 'name' => 'Created'],
            (object) ['id' => 'Updated', 'name' => 'Updated'],
            (object) ['id' => 'Deleted', 'name' => 'Deleted'],
            (object) ['id' => 'Trashed', 'name' => 'Trashed'],
            (object) ['id' => 'Restored', 'name' => 'Restored'],
        ]);
    }

    public static function activityLogSubjectTypes()
    {
        return collect([
            (object) ['id' => 'User', 'name' => 'User'],
        ]);
    }
}
