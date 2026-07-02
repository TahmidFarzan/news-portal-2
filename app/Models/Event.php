<?php
namespace App\Models;

use App\Helpers\MediaHelper;
use App\Observers\EventObserver;
use App\Policies\EventPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('events')]
#[Fillable([
        'name', 'brief', 'slug',
        'language_id', 'created_by_id',
        "seo_brief", 'seo_title', 'seo_keywords',
        "is_current", "position",
    ])]
#[UsePolicy(EventPolicy::class)]
#[ObservedBy([EventObserver::class])]
class Event extends Model implements HasMedia
{
    use HasFactory, LogsActivity, HasSlug, InteractsWithMedia;

    protected $appends = [
        'public_url', 'is_recent_created',
        "feeds_rss_url", "feeds_atom_url", "sitemap_url",
        'media_collection_name',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'brief', 'slug', "is_current",
                "seo_brief", 'seo_title', 'seo_keywords',
            ])
            ->useLogName('Event')
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
            ->generateSlugsFrom("name")
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(255)
            ->usingSuffixGenerator(fn() => Str::lower(Str::random(5)));
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
        return "Event";
    }

    public function getPublicUrlAttribute(): ?string
    {
        $url = null;

        if ($this->slug) {
            $url = route("event.news", ['slug' => $this->slug]);
        }

        return $url;
    }

    public function getFeedsAtomUrlAttribute(): ?string
    {

        $url = null;

        if ($this->slug) {
            $url = route("feeds.atom.event.news", ['slug' => $this->slug]);
        }

        return $url;
    }

    public function getFeedsRSSUrlAttribute(): ?string
    {
        $url = null;

        if ($this->slug) {
            $url = route("feeds.rss.event.news", ['slug' => $this->slug]);
        }

        return $url;
    }

    public function getSitemapUrlAttribute(): ?string
    {
        $url = null;

        if ($this->slug) {
            $url = route("sitemaps.event.news", ['slug' => $this->slug]);
        }

        return $url;
    }

    public function getIsRecentCreatedAttribute(): bool
    {
        $current         = now();
        $publishedAt     = $this->created_at;
        $intervalInHours = $current->diffInHours($publishedAt);
        return $intervalInHours < 72;
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function desktopBannerImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection_name', $this->media_collection_name)
            ->whereJsonContains('custom_properties->role', MediaHelper::ROLE_EVENT_BANNER_IMAGE_DESKTOP);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function latestActivityLog(): MorphOne
    {
        return $this->morphOne(Activity::class, 'subject')->latestOfMany();
    }

    public function mobileBannerImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection_name', $this->media_collection_name)
            ->whereJsonContains('custom_properties->role', MediaHelper::ROLE_EVENT_BANNER_IMAGE_MOBILE);
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    public function navBreadcrumbs(): array
    {
        $breadcrumbs = [];

        if ($this->ancestorsAndSelf()->breadthFirst()->count() > 0) {
            foreach ($this->ancestorsAndSelf()->breadthFirst()->get() as $rEvent) {
                $breadcrumb = ['name' => $rEvent->name, 'url' => $rEvent->public_url, 'description' => $rEvent->brief];
                array_push($breadcrumbs, $breadcrumb);
            }
        }

        return $breadcrumbs;
    }
}
