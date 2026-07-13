<?php
namespace App\Observers;

use App\Models\Event;
use Illuminate\Support\Str;
use App\Jobs\DeleteEventRelationsJob;

class EventObserver
{
    public function deleting(Event $event): void
    {
        DeleteEventRelationsJob::dispatchSync($event->id);
    }


}
