<?php
namespace App\Models;

use App\Helpers\MediaHelper;
use App\Helpers\NewsHelper;
use App\Observers\NewsObserver;
use App\Policies\NewsPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('newses')]
#[Fillable([
        'news_type','language_id', 'category_id', 'event_id', 'location_id',
        'title', 'sub_title', "content_shoulder", 'brief',
        "body", "video_url",'writer', 'source',
        "seo_brief", 'seo_title', 'seo_keywords',
        'created_by_id', 'slug', 'is_published',
    ])]
#[UsePolicy(NewsPolicy::class)]
#[ObservedBy([NewsObserver::class])]
class News extends Model implements HasMedia
{
    use HasFactory, LogsActivity, HasSlug, InteractsWithMedia;

    protected $appends = [
        'public_url',
        'is_recent_created',
        "feeds_rss_url", "feeds_atom_url",
        'feature_image', 'feature_image_mobile',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'news_type','language_id', 'category_id', 'event_id', 'location_id',
                'title', 'sub_title', "content_shoulder", 'brief',
                "body", "video_url", 'writer', 'source',
                "seo_brief", 'seo_title', 'seo_keywords',
                'slug', 'is_published',
            ])
            ->useLogName('News')
            ->setDescriptionForEvent(fn(string $eventName) => "The record has been {$eventName}.")
            ->logOnlyDirty()
            ->logExcept([
                'id',
                'created_by_id',
                'created_at',
            ])
            ->dontLogEmptyChanges();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->saveSlugsTo('slug')
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection($this->media_collection_name);
    }

    public function registerMediaConversions($spatieMedia = null): void
    {

        $this->addMediaConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION)
            ->format(MediaHelper::DEFAULT_MEDIA_CONVERSION)
            ->quality(80)
            ->performOnCollections($this->media_collection_name)
            ->queued();
    }

    public function getMediaCollectionNameAttribute(): string
    {
        return "News";
    }

    public function getPublicUrlAttribute(): string
    {
        $url = null;

        return $url ?? "";
    }

    public function getFeedsAtomUrlAttribute(): string
    {
        return "";
    }

    public function getFeedsRSSUrlAttribute(): string
    {
        return "";
    }

    public function getIsRecentCreatedAttribute(): bool
    {
        $current         = now();
        $publishedAt     = $this->created_at;
        $intervalInHours = $current->diffInHours($publishedAt);
        return $intervalInHours < 72;
    }

    public function getFeatureImageMobileAttribute(): ?Media
    {
        $image          = null;
        $collectionName = $this->media_collection_name;
        $roleParameter  = ["role" => MediaHelper::MEDIA_ROLE_NEWS_FEATURE_IMAGE_MOBILE];

        if ($this->hasMedia($collectionName, $roleParameter)) {
            $imageMedia = $this->getMedia($collectionName, $roleParameter)
                ->filter(fn($mediaItem) => stripos($mediaItem->mime_type, 'image/') === 0)
                ->first();

            if (isset($imageMedia)) {

                $imageMedia->media_url    = $imageMedia->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $imageMedia->getUrl(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $imageMedia->getUrl();
                $imageMedia->media_srcset = $imageMedia->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $imageMedia->getSrcset(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $imageMedia->getSrcset();

                $image = $imageMedia;
            }
        }

        return $image;
    }

    public function getFeatureImageAttribute(): ?Media
    {
        $image          = null;
        $collectionName = $this->media_collection_name;
        $roleParameter  = ["role" => MediaHelper::MEDIA_ROLE_NEWS_FEATURE_IMAGE];

        if ($this->hasMedia($collectionName, $roleParameter)) {
            $imageMedia = $this->getMedia($collectionName, $roleParameter)
                ->filter(fn($mediaItem) => stripos($mediaItem->mime_type, 'image/') === 0)
                ->first();

            if (isset($imageMedia)) {

                $imageMedia->media_url    = $imageMedia->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $imageMedia->getUrl(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $imageMedia->getUrl();
                $imageMedia->media_srcset = $imageMedia->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $imageMedia->getSrcset(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $imageMedia->getSrcset();

                $image = $imageMedia;
            }
        }

        return $image;
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function contributors(): BelongsToMany
    {
        return $this->belongsToMany(Contributor::class, 'contributor_news')->withTimestamps();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function latestActivityLog(): MorphOne
    {
        return $this->morphOne(Activity::class, 'subject')->latestOfMany();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'news_tag')->withTimestamps();
    }

    public function navBreadcrumbs(): array
    {
        $mainCategory = $this->category;

        if (! $mainCategory) {
            return [];
        }

        return $mainCategory
            ->ancestorsAndSelf()
            ->breadthFirst()
            ->get()
            ->map(fn($category) => [
                'name'        => $category->name,
                'url'         => $category->public_url,
                'description' => $category->brief,
            ])
            ->toArray();
    }

    public function images(): ?MediaCollection
    {
        $images         = null;
        $collectionName = $this->media_collection_name;

        if ($this->hasMedia($collectionName)) {
            $images = $this->getMedia($collectionName)->filter(function ($mediaItem) {
                return stripos($mediaItem->mime_type, 'image/') === 0;
            })->sortBy([
                ['order_column', 'asc'],
                ['id', 'asc'],
            ]);
        }
        return $images;
    }

    public function videos(): ?MediaCollection
    {
        $videos         = null;
        $collectionName = $this->media_collection_name;

        if ($this->hasMedia($collectionName)) {
            $videos = $this->getMedia($collectionName)->filter(function ($mediaItem) {
                return stripos($mediaItem->mime_type, 'video/') === 0;
            })->sortBy([
                ['order_column', 'asc'],
                ['id', 'asc'],
            ]);
        }
        return $videos;
    }

    public function audios(): ?MediaCollection
    {
        $audios         = null;
        $collectionName = $this->media_collection_name;

        if ($this->hasMedia($collectionName)) {
            $audios = $this->getMedia($collectionName)->filter(function ($mediaItem) {
                return stripos($mediaItem->mime_type, 'audio/') === 0;
            })->sortBy([
                ['order_column', 'asc'],
                ['id', 'asc'],
            ]);
        }
        return $audios;
    }

}
