<?php

namespace App\Models;

use App\Helpers\SettingHelper;
use App\Observers\SettingObserver;
use App\Policies\SettingPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('settings')]
#[Fillable([
    'group',
    'key',
    'label',
    'type',
    'value',
    'slug',
])]
#[UsePolicy(SettingPolicy::class)]
#[ObservedBy([SettingObserver::class])]
class Setting extends Model
{
    use HasFactory, LogsActivity, HasSlug;

    protected $appends = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => self::castValueFromStorage(
                $value,
                $attributes['type'] ?? null
            ),
            set: fn ($value, array $attributes) => self::castValueForStorage(
                $value,
                $attributes['type'] ?? $this->type ?? null
            )
        );
    }

    protected static function castValueFromStorage(mixed $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            SettingHelper::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            SettingHelper::TYPE_INTEGER => (int) $value,
            SettingHelper::TYPE_FLOAT,
            SettingHelper::TYPE_DECIMAL => (float) $value,
            SettingHelper::TYPE_JSON,
            SettingHelper::TYPE_ARRAY => json_decode($value, true),
            default => $value,
        };
    }

    protected static function castValueForStorage(mixed $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            SettingHelper::TYPE_BOOLEAN => self::prepareBooleanValue($value),
            SettingHelper::TYPE_INTEGER => (string) ((int) $value),
            SettingHelper::TYPE_FLOAT,
            SettingHelper::TYPE_DECIMAL => (string) ((float) $value),
            SettingHelper::TYPE_JSON,
            SettingHelper::TYPE_ARRAY => self::prepareJsonValue($value),
            default => (string) $value,
        };
    }

    protected static function prepareBooleanValue(mixed $value): string
    {
        $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($boolean === null) {
            throw new InvalidArgumentException('The value must be a valid boolean.');
        }

        return $boolean ? '1' : '0';
    }

    protected static function prepareJsonValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value);

            if ($encoded !== false) {
                return $encoded;
            }
        }

        if (is_string($value)) {
            json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }

        throw new InvalidArgumentException('The value must be an array, object, or valid JSON string.');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'group',
                'key',
                'label',
                'type',
                'value',
                'slug',
            ])
            ->useLogName('Setting')
            ->setDescriptionForEvent(fn (string $eventName) => "The record has been {$eventName}.")
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
                $mainSlug = Str::uuid();
                $randomString = Str::random(11);
                $createdAt = $model->created_at ?? now();
                $createdAt = $createdAt->format('HisdmY');

                return "{$createdAt}-{$randomString}-{$mainSlug}";
            })
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(255);
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
