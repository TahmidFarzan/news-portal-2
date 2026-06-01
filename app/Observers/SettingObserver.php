<?php

namespace App\Observers;


use App\Models\Setting;
use Illuminate\Support\Str;
use App\Jobs\DeleteSettingRelationsJob;

class SettingObserver
{
    public function deleting(Setting $setting): void
    {
        DeleteSettingRelationsJob::dispatchSync($setting->id);
    }
}
