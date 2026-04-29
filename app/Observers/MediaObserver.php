<?php
namespace App\Observers;

use App\Events\MediaUpdatedEvent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    public function updating(Media $media): void
    {
        $dirty = $media->getDirty();

        if (! empty($dirty)) {
            event(new MediaUpdatedEvent($media, $dirty));
        }
    }
}
