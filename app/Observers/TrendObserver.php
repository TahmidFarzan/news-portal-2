<?php
namespace App\Observers;

use App\Models\Trend;
use Illuminate\Support\Str;
use App\Jobs\DeleteTrendRelationsJob;

class TrendObserver
{
    public function deleting(Trend $trend): void
    {
        DeleteTrendRelationsJob::dispatchSync($trend->id);
    }
}
