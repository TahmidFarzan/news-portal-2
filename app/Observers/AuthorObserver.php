<?php
namespace App\Observers;

use App\Jobs\SyncAuthorSitemapJob;
use App\Models\Author;
use Illuminate\Support\Str;
use App\Jobs\DeleteAuthorRelationsJob;

class AuthorObserver
{
    public function deleting(Author $author): void
    {
        DeleteAuthorRelationsJob::dispatchSync($author->id);
    }

    public function created(Author $author): void
    {
        SyncAuthorSitemapJob::dispatch();
    }

    public function deleted(Author $author): void
    {
        SyncAuthorSitemapJob::dispatch();
    }

}
