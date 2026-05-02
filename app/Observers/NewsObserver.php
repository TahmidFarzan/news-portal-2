<?php
namespace App\Observers;

use App\Jobs\SyncNewsSitemapJob;
use App\Models\News;
use Illuminate\Support\Str;
use App\Jobs\DeleteNewsRelationsJob;

class NewsObserver
{
    public function creating(News $news): void
    {
        $this->treeUpdate($news);
    }

    public function updating(News $news): void
    {
        $this->treeUpdate($news);
    }

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

    private function treeUpdate(News $news)
    {
        $name = $news->name;
        $slug = Str::slug($news->name);

        $nameTree = $name;
        $slugTree = $slug;
        if ($news->parent_id) {
            if (! $news->relationLoaded('parent')) {
                $news->load('parent');
            }
            $slugTree = "{$news->parent->slug_tree}/{$slugTree}";
            $nameTree = "{$news->parent->heading_tree} - {$nameTree}";
        }
        $news->slug_tree = $slugTree;
        $news->heading_tree = $nameTree;
    }
}
