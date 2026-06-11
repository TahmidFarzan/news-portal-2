<?php

namespace App\Actions\Sluggable;

use Illuminate\Support\Str;
use Spatie\Sluggable\Actions\GenerateSlugAction;
use Spatie\Sluggable\SlugOptions;

class UnicodeGenerateSlugAction extends GenerateSlugAction
{
    public function slugifySource(string $source, SlugOptions $options): string
    {
        $separator = $options->slugSeparator ?: '-';

        $slug = Str::lower(trim($source));

        $slug = preg_replace('/[^\p{L}\p{M}\p{N}\s\-_]+/u', '', $slug) ?? '';
        $slug = preg_replace('/[\s_]+/u', $separator, $slug) ?? '';
        $slug = preg_replace('/' . preg_quote($separator, '/') . '+/u', $separator, $slug) ?? '';

        return trim($slug, $separator);
    }
}
