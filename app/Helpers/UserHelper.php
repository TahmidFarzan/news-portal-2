<?php
namespace App\Helpers;

class UserHelper
{
    public const USER_ROLE_ADMIN     = 'Admin';
    public const USER_ROLE_NEWS_DESK = 'News Desk';

    private const USER_GENDER_MALE   = 'Male';
    private const USER_GENDER_FEMALE = 'Female';
    private const USER_GENDER_OTHER  = 'Other';

    private const USER_RELIGION_ISLAM     = 'Islam';
    private const USER_RELIGION_HINDU     = 'Hindu';
    private const USER_RELIGION_CHRISTIAN = 'Christian';
    private const USER_RELIGION_OTHER     = 'Other';

    private const USER_MARITAL_SINGLE    = 'Single';
    private const USER_MARITAL_MARRIED   = 'Married';
    private const USER_MARITAL_DIVORCED  = 'Divorced';
    private const USER_MARITAL_SEPARATED = 'Separated';
    private const USER_MARITAL_OTHER     = 'Other';

    public static function userRoles()
    {
        return collect([
            (object) ['id' => self::USER_ROLE_ADMIN, 'name' => 'Admin'],
            (object) ['id' => self::USER_ROLE_NEWS_DESK, 'name' => 'News Desk'],
        ]);
    }

    public static function genders()
    {
        return collect([
            (object) ['id' => self::USER_GENDER_MALE, 'name' => 'Male'],
            (object) ['id' => self::USER_GENDER_FEMALE, 'name' => 'Female'],
            (object) ['id' => self::USER_GENDER_OTHER, 'name' => 'Other'],
        ]);
    }

    public static function religions()
    {
        return collect([
            (object) ['id' => self::USER_RELIGION_ISLAM, 'name' => 'Islam'],
            (object) ['id' => self::USER_RELIGION_HINDU, 'name' => 'Hindu'],
            (object) ['id' => self::USER_RELIGION_CHRISTIAN, 'name' => 'Christian'],
            (object) ['id' => self::USER_RELIGION_OTHER, 'name' => 'Other'],
        ]);
    }

    public static function maritalStatuses()
    {
        return collect([
            (object) ['id' => self::USER_MARITAL_SINGLE, 'name' => 'Single'],
            (object) ['id' => self::USER_MARITAL_MARRIED, 'name' => 'Married'],
            (object) ['id' => self::USER_MARITAL_DIVORCED, 'name' => 'Divorced'],
            (object) ['id' => self::USER_MARITAL_SEPARATED, 'name' => 'Separated'],
            (object) ['id' => self::USER_MARITAL_OTHER, 'name' => 'Other'],
        ]);
    }
}
