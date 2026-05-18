<?php

namespace App\Observers;


use App\Models\Menu;
use Illuminate\Support\Str;
use App\Jobs\DeleteMenuRelationsJob;

class MenuObserver
{
    public function deleting(Menu $menu): void
    {
        DeleteMenuRelationsJob::dispatchSync($menu->id);
    }
}
