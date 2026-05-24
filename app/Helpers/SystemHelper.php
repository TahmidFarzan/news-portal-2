<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class SystemHelper
{
    public const LANGUAGE_DEFAULT_CODE  = 'en';
    public const LANGUAGE_EXTRA_BN_CODE = 'bn';

    public static function toOptions(array $items): Collection
    {
        return collect($items)->map(fn ($item) => (object) [
            'id' => $item,
            'name' => $item,
        ]);
    }
}
