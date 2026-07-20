<?php
namespace App\Models;

use App\Helpers\PageHelper;
use App\Observers\PageObserver;
use App\Policies\PagePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

#[Table('pages')]
#[Fillable([
        'title', 'brief', 'slug', 'body',
        'language_id', 'created_by_id', "parent_id",
        "seo_brief", 'seo_title', 'seo_keywords',
        'default_use_as', 'is_default', 'is_published',
    ])]
#[UsePolicy(PagePolicy::class)]
#[ObservedBy([PageObserver::class])]
class Page extends Model
{
    use HasFactory, LogsActivity, HasSlug, HasRecursiveRelationships;

    protected $appends = [
        'public_url', 'is_recent_created',
        "indentation_title", "has_parent",
        "has_descendants",
    ];

    protected function casts(): array
    {
        return [
            'is_default'   => 'boolean',
            'is_published' => 'boolean',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title', 'brief', 'slug', "parent_id",
                "seo_brief", 'seo_title', 'seo_keywords',
                'default_use_as', 'is_default', 'is_published',
            ])
            ->useLogName('Page')
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
            ->usingSuffixGenerator(fn() => Str::lower(Str::random(5)));
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
        if (! $this->slug_tree || ! $this->slug) {
            return $url;
        }

        if (! $this->is_default) {
            $url = route("page", ['slugTree' => $this->slug_tree]);
            if (! $this->language->is_default) {
                $url = route("localized.page", ["languageCode" => $this->language->code, 'slugTree' => $this->slug_tree]);
            }
            return $url;
        }

        return match ($this->default_use_as) {
            PageHelper::DAFAULT_USE_AS_HOME   => $this->language->is_default ? route('home') : route('localized.home', ["languageCode" => $this->language->code]),
            PageHelper::DAFAULT_USE_AS_LATEST => $this->language->is_default ? route('latest') : route('localized.latest', ["languageCode" => $this->language->code]),
            PageHelper::DAFAULT_USE_AS_SEARCH => $this->language->is_default ? route('search') : route('localized.search', ["languageCode" => $this->language->code]),
            default                           => null,
        };
    }

    public function getIsRecentCreatedAttribute(): bool
    {
        $current         = now();
        $publishedAt     = $this->created_at;
        $intervalInHours = $current->diffInHours($publishedAt);
        return $intervalInHours < 72;
    }

    public function getHasParentAttribute(): bool
    {
        return isset($this->parent_id) ? true : false;
    }

    public function getHasDescendantsAttribute(): bool
    {
        return ($this->descendants()->count() > 0) ? true : false;
    }

    public function getIndentationTitleAttribute(): ?string
    {
        if (! $this->title_tree) {
            return null;
        }

        $parts = explode(' - ', $this->title_tree);

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

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function latestActivityLog(): MorphOne
    {
        return $this->morphOne(Activity::class, 'subject')->latestOfMany();
    }

}
