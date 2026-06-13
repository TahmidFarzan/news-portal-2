<?php
namespace App\Models;

use App\Observers\MenuItemObserver;
use App\Policies\MenuItemPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

#[Table('menu_items')]
#[Fillable([
        'name', 'slug', 'position',
        "menu_id", 'language_id', 'created_by_id',
        "url", "parent_id",
        "model_type", "model_id",
    ])]
#[UsePolicy(MenuItemPolicy::class)]
#[ObservedBy([MenuItemObserver::class])]
class MenuItem extends Model
{
    use HasFactory, LogsActivity, HasSlug, HasRecursiveRelationships;

    protected $appends = ["public_url", "has_parent", "has_descendants", "indentation_name", "is_custom_url"];

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
                'name',
                "menu_id", 'language_id', 'created_by_id',
                "url", "parent_id",
                "model_type", "model_id",
            ])
            ->useLogName('MenuItem')
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

    public function getParentKeyName()
    {
        return 'parent_id';
    }

    public function getHasParentAttribute(): bool
    {
        return isset($this->parent_id) ? true : false;
    }

    public function getIsCustomUrlAttribute(): bool
    {
        return (($this->model_type == null) || ($this->model_id == null)) ? true : false;
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

    public function getPublicUrlAttribute(): ?string
    {
        $url = $this->url;

        if ($this->model) {
            $url = $this->model?->public_url;
        }

        if(!$this->url && !$this->model && $this->slug_tree){
            $url = route("page",["slugTree" => $this->slug_tree]);
        }

        return $url ?? null;
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

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
