<?php
namespace App\Observers;


use App\Models\Tag;
use Illuminate\Support\Str;
use App\Jobs\DeleteTagRelationsJob;

class TagObserver
{
    public function deleting(Tag $tag): void
    {
        DeleteTagRelationsJob::dispatchSync($tag->id);
    }
}
