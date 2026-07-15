<?php
namespace App\Helpers;

use Illuminate\Support\Collection;

class SystemHelper
{
    public const LANGUAGE_EN_CODE  = 'en';
    public const LANGUAGE_BN_CODE = 'bn';

    public const SITE_DEFAULT_LANGUAGE = self::LANGUAGE_EN_CODE;

    public static function toOptions(array $items): Collection
    {
        return collect($items)->map(fn($item) => (object) [
            'id'   => $item,
            'name' => $item,
        ]);
    }
}
