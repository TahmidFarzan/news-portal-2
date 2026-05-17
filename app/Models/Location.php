<?php
namespace App\Models;

use App\Observers\LocationObserver;
use App\Policies\LocationPolicy;
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
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

#[Table('locations')]
#[Fillable([
        'name', 'brief', 'parent_id', 'slug', 'category_id',
        'language_id', 'name_tree', "slug_tree", 'created_by_id',
        "seo_brief", 'seo_title', 'seo_keywords',
        'latitude', 'longitude',
    ])]
#[UsePolicy(LocationPolicy::class)]
#[ObservedBy([LocationObserver::class])]
class Location extends Model
{
    use HasFactory, LogsActivity, HasSlug, HasRecursiveRelationships;

    protected $appends = [
        'public_url', "has_parent", "indentation_name",
        "has_descendants", "feeds_rss_url", "feeds_atom_url", "sitemap_url",

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
                'name', 'brief', 'parent_id', 'slug', 'category_id',
                'latitude', 'longitude', 'name_tree', "slug_tree",
            ])
            ->useLogName('Location')
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

    public function getParentKeyName()
    {
        return 'parent_id';
    }

    public function getPublicUrlAttribute(): string
    {
        $url = null;

        return $url ?? "";
    }

    public function getFeedsAtomUrlAttribute(): string
    {
        return route("feeds.atom.locations.newses", ['slugTree' => $this->slug_tree]);
    }

    public function getFeedsRSSUrlAttribute(): string
    {
        return route("feeds.rss.locations.newses", ['slugTree' => $this->slug_tree]);
    }

    public function getSitemapUrlAttribute(): string
    {
        return route("sitemaps.locations.newses", ['slugTree' => $this->slug_tree]);
    }

    public function getHasParentAttribute(): bool
    {
        return isset($this->parent_id) ? true : false;
    }

    public function getHasDescendantsAttribute(): bool
    {
        return ($this->descendants()->count() > 0) ? true : false;
    }

    public function getIndentationNameAttribute(): ?string
    {
        if (! $this->name_tree) {
            return null;
        }

        $parts = explode(' - ', $this->name_tree);

        $last        = trim(array_pop($parts));
        $transformed = str_repeat('-- ', count($parts)) . $last;

        return trim($transformed);
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function navBreadcrumbs(): array
    {
        $breadcrumbs = [];

        if ($this->ancestorsAndSelf()->breadthFirst()->count() > 0) {
            foreach ($this->ancestorsAndSelf()->breadthFirst()->get() as $rLocation) {
                $breadcrumb = ['name' => $rLocation->name, 'url' => $rLocation->public_url, 'description' => $rLocation->brief];
                array_push($breadcrumbs, $breadcrumb);
            }
        }

        return $breadcrumbs;
    }
}
