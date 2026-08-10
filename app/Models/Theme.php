<?php

namespace App\Models;

use App\Helpers\ThemeHelper;
use App\Observers\ThemeObserver;
use App\Policies\ThemePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Str;

#[Table('themes')]
#[Fillable([
    'name',
    'slug',
    'options',
])]
#[UsePolicy(ThemePolicy::class)]
#[ObservedBy([ThemeObserver::class])]
class Theme extends Model
{
    use HasFactory, LogsActivity, HasSlug;

    protected $appends = [];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                'options',
            ])
            ->useLogName('Theme')
            ->setDescriptionForEvent(
                fn (string $eventName) => "The record has been {$eventName}."
            )
            ->logOnlyDirty()
            ->logExcept([
                'id',
                'created_at',
                'updated_at',
            ])
            ->dontLogEmptyChanges();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->saveSlugsTo('slug')
            ->generateSlugsFrom('name')
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(255)
            ->usingSuffixGenerator(
                fn () => Str::lower(Str::random(5)) . '-' . now()->format('HisdmY')
            );
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function latestActivityLog(): MorphOne
    {
        return $this->morphOne(Activity::class, 'subject')->latestOfMany();
    }
}
