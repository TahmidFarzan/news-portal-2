<?php
namespace App\Observers;


use App\Models\Contributor;
use Illuminate\Support\Str;
use App\Jobs\DeleteContributorRelationsJob;

class ContributorObserver
{
    public function deleting(Contributor $contributor): void
    {
        DeleteContributorRelationsJob::dispatchSync($contributor->id);
    }

}
