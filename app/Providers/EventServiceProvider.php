<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

use App\Listeners\MediaCreatedListener;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

use Spatie\Activitylog\Models\Activity;
use App\Observers\ActivityObserver;

class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(
            MediaHasBeenAddedEvent::class,
            [MediaCreatedListener::class, 'handle']
        );
        Activity::observe(ActivityObserver::class);
    }
}
