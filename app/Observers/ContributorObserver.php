<?php
namespace App\Observers;

use App\Jobs\SyncContributorSitemapJob;
use App\Models\Contributor;
use Illuminate\Support\Str;
use App\Jobs\DeleteContributorRelationsJob;

class ContributorObserver
{
    public function deleting(Contributor $contributor): void
    {
        DeleteContributorRelationsJob::dispatchSync($contributor->id);
    }

    public function created(Contributor $contributor): void
    {
        SyncContributorSitemapJob::dispatch();
    }

    public function deleted(Contributor $contributor): void
    {
        SyncContributorSitemapJob::dispatch();
    }

}
