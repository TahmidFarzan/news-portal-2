<?php
namespace App\Helpers;

use Illuminate\Support\Collection;

class SystemHelper
{
    public const DEFAULT_LANGUAGE_CODE  = 'en';
    public const EXTRA_LANGUAGE_BN_CODE = 'bn';

    public static function toOptions(array $items): Collection
    {
        return collect($items)->map(fn($item) => (object) [
            'id'   => $item,
            'name' => $item,
        ]);
    }
}
