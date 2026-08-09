<?php
namespace App\Models;

use App\Observers\GoogleAdsenseObserver;
use App\Policies\GoogleAdsensePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('google_ad_sences')]
#[Fillable([
        'name',
        'slug',
        "slot_id",
        "client_id",
        "type",
        "position",
        'created_by_id',
    ])]
#[UsePolicy(GoogleAdsensePolicy::class)]
#[ObservedBy([GoogleAdsenseObserver::class])]
class GoogleAdsense extends Model
{
    use HasFactory, LogsActivity, HasSlug;

    protected $appends = [];

    protected function casts(): array
    {
        return [
            "use_full_width_responsive" => 'boolean',
            'created_at'                => 'datetime',
            'updated_at'                => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                "slot_id",
                "client_id",
                "type",
                "position",
            ])
            ->useLogName('GoogleAdsense')
            ->setDescriptionForEvent(fn(string $eventName) => "The record has been {$eventName}.")
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
            ->generateSlugsFrom(function ($model) {
                return "{$model->group}-{$model->label}-{$model->label}";
            })
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(255)
            ->usingSuffixGenerator(fn() => Str::lower(Str::random(5)));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
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
