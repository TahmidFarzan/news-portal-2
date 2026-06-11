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
        'latitude', 'longitude', 'boundary_geojson',
        'boundary_north', 'boundary_south',
        'boundary_east', 'boundary_west',
    ])]
#[UsePolicy(LocationPolicy::class)]
#[ObservedBy([LocationObserver::class])]
class Location extends Model
{
    use HasFactory, LogsActivity, HasSlug, HasRecursiveRelationships;

    protected $appends = [
        'public_url', "has_parent", "indentation_name", "enable_map",
        "has_descendants", "feeds_rss_url", "feeds_atom_url", "sitemap_url",

    ];

    protected function casts(): array
    {
        return [
            'latitude'         => 'decimal:7',
            'longitude'        => 'decimal:7',

            'boundary_geojson' => 'array',
            'boundary_north'   => 'decimal:7',
            'boundary_south'   => 'decimal:7',
            'boundary_east'    => 'decimal:7',
            'boundary_west'    => 'decimal:7',

            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'brief', 'parent_id', 'slug', 'category_id',
                'name_tree', "slug_tree",
                'latitude', 'longitude', 'boundary_geojson',
                'boundary_north', 'boundary_south',
                'boundary_east', 'boundary_west',
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
            ->generateSlugsFrom("name")
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(255)
            ->usingSuffixGenerator(fn () => Str::lower(Str::random(5)));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getParentKeyName()
    {
        return 'parent_id';
    }

    public function getPublicUrlAttribute(): ?string
    {
        $url = null;

        if ($this->slug_tree) {
            $url = route("location.news", ['slugTree' => $this->slug_tree]);
        }

        return $url;
    }

    public function getFeedsAtomUrlAttribute(): ?string
    {
        $url = null;

        if ($this->slug_tree) {
            $url = route("feeds.atom.location.news", ['slugTree' => $this->slug_tree]);
        }

        return $url;
    }

    public function getFeedsRSSUrlAttribute(): ?string
    {
        $url = null;

        if ($this->slug_tree) {
            $url = route("feeds.rss.location.news", ['slugTree' => $this->slug_tree]);
        }

        return $url;
    }

    public function getSitemapUrlAttribute(): ?string
    {
        $url = null;

        if ($this->slug_tree) {
            $url = route("sitemaps.location.news", ['slugTree' => $this->slug_tree]);
        }

        return $url;
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

    public function getEnableMapAttribute(): string
    {
        $enableMap = false;

        if ($this->latitude && $this->longitude && $this->boundary_geojson && $this->boundary_north && $this->boundary_south && $this->boundary_east && $this->boundary_west) {
            $enableMap = true;
        }

        return $enableMap;
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

    public function news(): HasMany
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
