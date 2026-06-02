<?php
namespace App\Observers;

use App\Models\BreakingNews;
use Illuminate\Support\Str;
use App\Jobs\DeleteBreakingNewsRelationsJob;

class BreakingNewsObserver
{
    public function deleting(BreakingNews $breakingNews): void
    {
        DeleteBreakingNewsRelationsJob::dispatchSync($breakingNews->id);
    }
}
