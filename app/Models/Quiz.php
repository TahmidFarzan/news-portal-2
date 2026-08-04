<?php

namespace App\Models;

use App\Observers\QuizObserver;
use App\Policies\QuizPolicy;
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

#[Table('quizzes')]
#[Fillable([
    'name',
    'brief',
    'slug',
    'language_id',
    "start_date",
    'end_date',
    "is_active",
    'created_by_id',
    "show_bellow_event",
    'enable_result',
    'max_winner',
])]
#[UsePolicy(QuizPolicy::class)]
#[ObservedBy([QuizObserver::class])]
class Quiz extends Model
{
    use HasFactory, LogsActivity, HasSlug;

    protected $appends = ["public_url", "show_result"];

    protected function casts(): array
    {
        return [
            'start_date'        => 'date',
            'end_date'          => 'date',

            'enable_result' => 'boolean',
            'max_winner' => 'integer',

            'is_active'        => 'boolean',
            'show_bellow_event' => 'boolean',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'brief',
                'slug',
                "is_active",
                "language_id",
                "start_date",
                'end_date',
                "show_bellow_event",
                'enable_result',
                'max_winner',
            ])
            ->useLogName('Quiz')
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

    public function getPublicUrlAttribute(): ?string
    {
        $url = null;
        if (! $this->slug) {
            return $url;
        }

        $url = route("home.quizzes.details", ["slug" => $this->slug]);

        if ($this->language?->is_default == false) {
            $url = route("localized.home.quizzes.details", ["languageCode" => $this->language?->code, "slug" => $this->slug]);
        }

        return $url;
    }

    public function getShowResultAttribute(): bool
    {
        if (! $this->enable_result) {
            return false;
        }

        $endDate = $this->end_date ?? now();

        return $endDate->copy()->addDays(30)->gte(now());
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

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    public function quizResults(): HasMany
    {
        return $this->hasMany(QuizResult::class);
    }

    public function quizParticipants(): HasMany
    {
        return $this->hasMany(QuizParticipant::class);
    }
}
