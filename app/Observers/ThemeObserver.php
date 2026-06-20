<?php

namespace App\Observers;


use App\Models\Theme;
use Illuminate\Support\Str;
use App\Jobs\DeleteThemeRelationsJob;

class ThemeObserver
{
    public function deleting(Theme $theme): void
    {
        DeleteThemeRelationsJob::dispatchSync($theme->id);
    }
}
