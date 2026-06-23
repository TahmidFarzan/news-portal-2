<?php

namespace App\Observers;

use App\Models\GoogleAdsence;
use App\Jobs\DeleteGoogleAdsenceRelationsJob;

class GoogleAdsenceObserver
{
    public function deleting(GoogleAdsence $googleAdsence): void {
        DeleteGoogleAdsenceRelationsJob::dispatchSync($googleAdsence->id);
    }
}
