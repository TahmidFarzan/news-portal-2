<?php
namespace App\Observers;

use App\Jobs\DeleteNewsRelationsJob;
use App\Jobs\LatestNewsFeedCacheClearJob;
use App\Jobs\LatestNewsSitemapCacheClearJob;

use App\Models\News;

class NewsObserver
{
    public function deleting(News $news): void
    {
        DeleteNewsRelationsJob::dispatchSync($news->id);
    }

    public function created(News $news): void
    {
        LatestNewsSitemapCacheClearJob::dispatch();

        LatestNewsFeedCacheClearJob::dispatch();
    }

    public function updated(News $news): void
    {
        if (! $news->wasChanged('is_published')) {
            return;
        }


        LatestNewsSitemapCacheClearJob::dispatch();

        LatestNewsFeedCacheClearJob::dispatch();
    }
}
