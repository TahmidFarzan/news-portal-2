<?php
namespace App\Observers;

use App\Jobs\SyncNewsSitemapJob;
use App\Models\News;
use App\Jobs\DeleteNewsRelationsJob;

class NewsObserver
{
    public function deleting(News $news): void
    {
        DeleteNewsRelationsJob::dispatchSync($news->id);
    }

    public function created(News $news): void
    {
        SyncNewsSitemapJob::dispatch();
    }

    public function deleted(News $news): void
    {
        SyncNewsSitemapJob::dispatch();
    }
}
