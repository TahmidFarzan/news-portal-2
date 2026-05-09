<?php
namespace App\Observers;

use App\Jobs\SyncStorySitemapJob;
use App\Models\Story;
use App\Jobs\DeleteStoryRelationsJob;

class StoryObserver
{
    public function deleting(Story $story): void
    {
        DeleteStoryRelationsJob::dispatchSync($story->id);
    }

    public function created(Story $story): void
    {
        SyncStorySitemapJob::dispatch();
    }

    public function deleted(Story $story): void
    {
        SyncStorySitemapJob::dispatch();
    }
}
