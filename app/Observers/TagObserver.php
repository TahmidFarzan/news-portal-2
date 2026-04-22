<?php
namespace App\Observers;

use App\Jobs\SyncTagSitemapJob;
use App\Models\Tag;
use Illuminate\Support\Str;
use App\Jobs\DeleteTagRelationsJob;

class TagObserver
{
    public function deleting(Tag $tag): void
    {
        DeleteTagRelationsJob::dispatchSync($tag->id);
    }

    public function created(Tag $tag): void
    {
        SyncTagSitemapJob::dispatch();
    }

    public function deleted(Tag $tag): void
    {
        SyncTagSitemapJob::dispatch();
    }

}
