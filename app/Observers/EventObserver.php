<?php
namespace App\Observers;

use App\Jobs\SyncEventSitemapJob;
use App\Models\Event;
use Illuminate\Support\Str;
use App\Jobs\DeleteEventRelationsJob;

class EventObserver
{
    public function deleting(Event $event): void
    {
        DeleteEventRelationsJob::dispatchSync($event->id);
    }

    public function created(Event $event): void
    {
        SyncEventSitemapJob::dispatch();
    }

    public function deleted(Event $event): void
    {
        SyncEventSitemapJob::dispatch();
    }

}
