<?php
namespace App\Models;

use App\Helpers\MediaHelper;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('media_uploads')]
class MediaUpload extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $appends = [
        'media_collection_name',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection("MediaUpload");
    }

    public function registerMediaConversions($spatieMedia = null): void
    {
        $this->addMediaConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION)
            ->format(MediaHelper::DEFAULT_MEDIA_CONVERSION)
            ->quality(80)
            ->performOnCollections("MediaUpload")
            ->queued();
    }

    public function getMediaCollectionNameAttribute(): string
    {
        return "MediaUpload";
    }
}
