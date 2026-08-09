<?php

namespace App\Observers;

use App\Models\GoogleAdsense;
use App\Jobs\DeleteGoogleAdsenseRelationsJob;

class GoogleAdsenseObserver
{
    public function deleting(GoogleAdsense $googleAdsense): void {
        DeleteGoogleAdsenseRelationsJob::dispatchSync($googleAdsense->id);
    }
}
