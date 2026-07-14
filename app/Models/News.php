<?php
namespace App\Models;

use App\Helpers\MediaHelper;
use App\Helpers\SystemHelper;
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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

#[Table('news')]
#[Fillable([
        'news_type_id', 'language_id', 'category_id', 'event_id', 'location_id',
        'title', 'sub_title', "content_shoulder", 'brief',
        "body", "video_url", 'writer', 'source',
        "seo_brief", 'seo_title', 'seo_keywords',
        'created_by_id', 'slug', 'is_published', 'hit_count',
    ])]
#[UsePolicy(NewsPolicy::class)]
#[ObservedBy([NewsObserver::class])]
class News extends Model implements HasMedia
{
    use HasFactory, LogsActivity, HasSlug, InteractsWithMedia;

    protected $appends = [
        'public_url',
        "published_at", "title_with_published_at",
        'is_recent_created',
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
                'news_type_id', 'language_id', 'category_id', 'event_id', 'location_id',
                'title', 'sub_title', "content_shoulder", 'brief',
                "body", "video_url", 'writer', 'source',
                "seo_brief", 'seo_title', 'seo_keywords',
                'slug', 'is_published', 'hit_count',
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
            ->generateSlugsFrom("title")
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(255)
            ->usingSuffixGenerator(fn() => Str::lower(Str::random(5)) . '-' . now()->format('HisdmY'));
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

        $this->addMediaConversion(MediaHelper::DEFAULT_CONVERSION)
            ->format(MediaHelper::DEFAULT_CONVERSION_FORMAT)
            ->quality(100)
            ->performOnCollections($this->media_collection_name)
            ->queued();
    }

    public function getMediaCollectionNameAttribute(): string
    {
        return "News";
    }

    public function getPublicUrlAttribute(): ?string
    {
        $url = null;

        if ($this->slug) {
            $url = route("news.details", ["slug" => $this->slug]);
        }

        return $url;
    }

    public function getPublishedAtAttribute(): ?string
    {
        if (! $this->slug || ! $this->created_at) {
            return null;
        }

        $locale          = config('app.locale');
        $contentLanguage = $this->language?->code;

        $displayLanguage = SystemHelper::LANGUAGE_EN_CODE;

        if (
            $contentLanguage &&
            $contentLanguage !== $locale &&
            in_array($contentLanguage, [
                SystemHelper::LANGUAGE_EN_CODE,
                SystemHelper::LANGUAGE_BN_CODE,
            ], true)
        ) {
            $displayLanguage = $contentLanguage;
        } elseif (
            in_array($locale, [
                SystemHelper::LANGUAGE_EN_CODE,
                SystemHelper::LANGUAGE_BN_CODE,
            ], true)
        ) {
            $displayLanguage = $locale;
        }

        $publishedAt = $this->created_at;
        $seconds     = $publishedAt->diffInSeconds(now());

        $numbers = trans('time.numbers', [], $displayLanguage);

        $localize = static fn(string | int $value): string => strtr((string) $value, $numbers);

        if ($seconds < 60) {
            return $localize($seconds) . trans('time.second_ago', [], $displayLanguage);
        }

        if ($seconds < 3600) {
            return $localize((int) floor($seconds / 60)) . trans('time.minute_ago', [], $displayLanguage);
        }

        if ($seconds < 86400) {
            return $localize((int) floor($seconds / 3600)) . trans('time.hour_ago', [], $displayLanguage);
        }

        $publishedAtFormatted = $publishedAt->format(config('app.date_time_format'));

        $publishedAtFormatted = strtr(
            $publishedAtFormatted,
            trans('time.months', [], $displayLanguage)
        );

        $publishedAtFormatted = strtr(
            $publishedAtFormatted,
            trans('time.meridiem', [], $displayLanguage)
        );

        $publishedAtFormatted = strtr(
            $publishedAtFormatted,
            $numbers
        );

        return $publishedAtFormatted;
    }

    public function getIsRecentCreatedAttribute(): bool
    {
        $current         = now();
        $publishedAt     = $this->created_at;
        $intervalInHours = $current->diffInHours($publishedAt);
        return $intervalInHours < 72;
    }

    public function getTitleWithPublishedAtAttribute(): string
    {
        return "{$this->title} ({$this->published_at})";
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function breakingNews(): HasOne
    {
        return $this->hasOne(BreakingNews::class);
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

    public function featureImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection_name', $this->media_collection_name)
            ->whereJsonContains('custom_properties->role', MediaHelper::ROLE_NEWS_FEATURE_IMAGE);
    }

    public function featureImageMobile(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection_name', $this->media_collection_name)
            ->whereJsonContains('custom_properties->role', MediaHelper::ROLE_NEWS_FEATURE_IMAGE_MOBILE);
    }

    public function galleryImages(): MorphMany
    {

        return $this->morphMany(Media::class, 'model')
            ->where('collection_name', $this->media_collection_name)
            ->where('custom_properties->role', MediaHelper::ROLE_NEWS_GALLERY_IMAGE)
            ->orderBy('order_column')
            ->orderBy('id');
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

    public function newsType(): BelongsTo
    {
        return $this->belongsTo(NewsType::class);
    }

    public function newsPlacements(): HasMany
    {
        return $this->hasMany(NewsPlacement::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'news_tag')->withTimestamps();
    }

    public function relevantNews(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'news_relevant_news', 'news_id', 'relevant_news_id')->withTimestamps();
    }

    public function relatedNews(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'news_related_news', 'news_id', 'related_news_id')->withTimestamps();
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
