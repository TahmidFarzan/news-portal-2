<?php

namespace App\Observers;

use App\Models\GoogleAd;
use App\Jobs\DeleteGoogleAdRelationsJob;

class GoogleAdObserver
{
    public function deleting(GoogleAd $googleAd): void {
        DeleteGoogleAdRelationsJob::dispatchSync($googleAd->id);
    }
}
