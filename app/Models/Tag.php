<?php
namespace App\Models;

use App\Observers\TagObserver;
use App\Policies\TagPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('tags')]
#[Fillable([
        'name', 'brief', 'slug',
        'language_id', 'created_by_id',
        "seo_brief", 'seo_title', 'seo_keywords',
    ])]
#[UsePolicy(TagPolicy::class)]
#[ObservedBy([TagObserver::class])]
class Tag extends Model
{
    use HasFactory, LogsActivity, HasSlug;

    protected $appends = [
        'public_url', 'is_recent_created',
        "feeds_rss_url", "feeds_atom_url", "sitemap_url",
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'brief', 'slug',
                "seo_brief", 'seo_title', 'seo_keywords',
            ])
            ->useLogName('Tag')
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
            ->usingSuffixGenerator(fn () => Str::lower(Str::random(5)));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getPublicUrlAttribute(): ?string
    {
        $url = null;

        if($this->slug){
            $url = route("tag.news", ['slug' => $this->slug]);
        }

        return $url;
    }

    public function getFeedsAtomUrlAttribute(): ?string
    {
        $url = null;

        if ($this->slug) {
            $url = route("feeds.atom.tag.news", ['slug' => $this->slug]);
        }

        return $url;
    }

    public function getFeedsRSSUrlAttribute(): ?string
    {
        $url = null;

        if ($this->slug) {
            $url = route("feeds.rss.tag.news", ['slug' => $this->slug]);
        }

        return $url;
    }

    public function getSitemapUrlAttribute(): ?string
    {
        $url = null;

        if ($this->slug) {
            $url = route("sitemaps.tag.news", ['slug' => $this->slug]);
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

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function latestActivityLog(): MorphOne
    {
        return $this->morphOne(Activity::class, 'subject')->latestOfMany();
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'news_tag');
    }

    public function trend(): HasOne
    {
        return $this->hasOne(Trend::class);
    }

    public function navBreadcrumbs(): array
    {
        $breadcrumbs   = [];
        $breadcrumbs[] = [
            'name'        => $this->name,
            'url'         => $this->public_url,
            'description' => $this->brief,
        ];

        return $breadcrumbs;
    }
}
