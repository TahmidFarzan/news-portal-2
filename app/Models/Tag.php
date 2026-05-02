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
            'deleted_at' => 'datetime',
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

    public function getSitemapUrlAttribute(): string
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

    public function newses(): HasMany
    {
        return $this->hasMany(News::class);
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
