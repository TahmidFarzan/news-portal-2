<?php
namespace App\Observers;

use App\Jobs\DeleteNewsRelationsJob;
use App\Jobs\SyncLatestNewsFeedJob;
use App\Jobs\SyncLatestNewsSitemapJob;
use App\Jobs\SyncNewsFeedJob;
use App\Jobs\SyncNewsSitemapJob;
use App\Models\News;

class NewsObserver
{
    public function deleting(News $news): void
    {
        DeleteNewsRelationsJob::dispatchSync($news->id);
    }

    public function created(News $news): void
    {
        SyncNewsSitemapJob::dispatch();
        SyncLatestNewsSitemapJob::dispatch();

        SyncNewsFeedJob::dispatch();
        SyncLatestNewsFeedJob::dispatch();
    }

    public function updated(News $news): void
    {
        if (! $news->wasChanged('is_published')) {
            return;
        }

        SyncNewsSitemapJob::dispatch();
        SyncLatestNewsSitemapJob::dispatch();

        SyncNewsFeedJob::dispatch();
        SyncLatestNewsFeedJob::dispatch();
    }
}
