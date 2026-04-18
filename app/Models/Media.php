<?php
namespace App\Models;

use App\Helpers\MediaHelper;
use App\Traits\InteractsWithMediaExtend;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Fillable([
        'url',
        'slug',
        'mime_type',
        'created_by_id',
    ])]
class Media extends SpatieMedia implements HasMedia
{
    use HasFactory, HasSlug, LogsActivity, InteractsWithMedia, InteractsWithMediaExtend;

    protected $appends = ['icon', 'media_url', 'media_srcset'];

    protected $attributes = [
        'disk'            => 'public',
        'collection_name' => 'default',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->saveSlugsTo('slug')
            ->usingSeparator('-')
            ->generateSlugsFrom(function ($model) {
                $mainSlug     = Str::uuid();
                $randomString = Str::random(11);
                $createdAt    = $model->created_at ?? now();
                $createdAt    = $createdAt->format('HisdmY');
                return "{$createdAt}-{$randomString}-{$mainSlug}";
            })
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(255);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'slug', 'uuid', 'size', 'disk', 'url', 'media_type',
                'model_id', 'file_name', 'mime_type', 'model_type', 'updated_at',
                'order_column', 'manipulations', 'conversions_disk', 'collection_name',
                'responsive_images', 'custom_properties', 'generated_conversions',
            ])
            ->useLogName('Media')
            ->setDescriptionForEvent(fn(string $eventName) => "The record has been {$eventName}.")
            ->logOnlyDirty()
            ->logExcept([
                'id', 'created_by_id', 'created_at',
            ])
            ->dontLogEmptyChanges();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getIconAttribute(): string
    {
        $icon     = '<i class="fa-solid fa-circle-question"></i>';
        $type     = $this->getTypeFromMime();
        $mimeType = $this->mime_type;

        switch ($type) {
            case 'image':
                $icon = '<i class="fa-solid fa-image"></i>';
                break;
            case 'video':
                $icon = '<i class="fa-solid fa-video"></i>';
                break;
            case 'audio':
                $icon = '<i class="fa-solid fa-headphones"></i>';
                break;
            case 'other':
                $icon = '<i class="fa-solid fa-file"></i>';
                break;
            case 'application':
                if (str_contains($mimeType, 'zip') || str_contains($mimeType, 'rar')) {
                    $icon = '<i class="fa-solid fa-file-zipper"></i>';
                } elseif (str_contains($mimeType, 'json')) {
                    $icon = '<i class="fa-solid fa-file-code"></i>';
                } elseif (str_contains($mimeType, 'csv')) {
                    $icon = '<i class="fa-solid fa-file-csv"></i>';
                }
                break;
        }

        return $icon;
    }

    public function getMediaUrlAttribute(): string
    {
        return $this->hasGeneratedConversion('webp') ? $this->getUrl('webp') : $this->getUrl();
    }

    public function getMediaSrcsetAttribute(): string
    {
        return $this->hasGeneratedConversion('webp') ? $this->getSrcset('webp') : $this->getSrcset();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): ?User
    {
        $latestActivity = $this->activities()->latest()->first();
        return $latestActivity?->causer;
    }

    public function getUrl(string $conversionName = ''): string
    {
        if ($this->media_type === 'Url') {
            return $this->url;
        }

        return parent::getUrl($conversionName);
    }

    public function getImageRatio(): string
    {
        $ratio = '4:3';

        if (str_starts_with($this->mime_type, 'image/')) {
            $imageUrl = $this->getUrl();
            $headers  = @get_headers($imageUrl);

            if ($headers && str_contains($headers[0], '200')) {
                $imgSize = @getimagesize($imageUrl);

                if ($imgSize !== false && $imgSize[0] > 0 && $imgSize[1] > 0) {
                    $ratioValue = $imgSize[0] / $imgSize[1];

                    $ratio = match (true) {
                        abs($ratioValue - (4 / 3)) < 0.01  => '4:3',
                        abs($ratioValue - (2 / 3)) < 0.01  => '2:3',
                        abs($ratioValue - (3 / 2)) < 0.01  => '3:2',
                        abs($ratioValue - (16 / 9)) < 0.01 => '16:9',
                        default                            => '4:3',
                    };
                }
            }
        } else {
            $ratio = null;
        }

        return $ratio;
    }

    public function generateThumbnailImageHTML(string $conversion = '', string $class = '', array $attributes = []): string
    {
        $defaultImageUrl = MediaHelper::defaultDemoImage('16:9', "Thumbnail");

        $src = $this->hasGeneratedConversion($conversion)
            ? $this->getUrl($conversion)
            : $defaultImageUrl;

        return $this->img()
            ->attributes([
                'class'    => $class,
                'alt'      => $this->getCustomProperty('alt'),
                'data-src' => $src,
                'src'      => $defaultImageUrl,
            ] + $attributes)
            ->lazy();
    }
}
