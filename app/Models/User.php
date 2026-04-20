<?php
namespace App\Models;

use App\Helpers\MediaHelper;
use App\Observers\UserObserver;
use App\Policies\UserPolicy;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('users')]
#[Fillable([
        'name', 'email', 'slug', 'password', 'is_default',
        'user_role_id', 'is_admin', 'marital_status',
        'religion', 'gender', 'mobile', 'birth_date',
        'address', 'created_by_id',
    ])]
#[Hidden([
        'password', 'remember_token', 'is_default',
    ])]
#[UsePolicy(UserPolicy::class)]
#[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity, HasSlug, InteractsWithMedia;

    protected $appends = [
        'age', 'is_active', 'media_collection_name', 'profile_image',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date'        => 'date',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
            'deleted_at'        => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'password',
                'updated_at',
                'deleted_at',
                'email_verified_at',
                'is_default',
                'mobile',
                'gender',
                'religion',
                'address',
                'marital_status',
            ])
            ->useLogName('User')
            ->setDescriptionForEvent(fn(string $eventName) => "The record has been {$eventName}.")
            ->logOnlyDirty()
            ->logExcept([
                'id',
                'created_by_id',
                'created_at',
                'remember_token',
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection("User");
    }

    public function registerMediaConversions($spatieMedia = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(80)
            ->performOnCollections("User")
            ->queued();
    }

    public function getMediaCollectionNameAttribute(): string
    {
        return "User";
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return ($this->deleted_at == null) ? true : false;
    }

    public function getProfileImageAttribute(): ?Media
    {
        $image          = null;
        $collectionName = $this->media_collection_name;
        $roleParameter  = ["role" => MediaHelper::MEDIA_ROLE_PROFILE_IMAGE];

        if ($this->hasMedia($collectionName, $roleParameter)) {
            $imageMedia = $this->getMedia($collectionName, $roleParameter)
                ->filter(fn($mediaItem) => stripos($mediaItem->mime_type, 'image/') === 0)
                ->first();

            if (isset($imageMedia)) {

                $imageMedia->media_url    = $imageMedia->hasGeneratedConversion('webp') ? $imageMedia->getUrl('webp') : $imageMedia->getUrl();
                $imageMedia->media_srcset = $imageMedia->hasGeneratedConversion('webp') ? $imageMedia->getSrcset('webp') : $imageMedia->getSrcset();

                $image = $imageMedia;
            }
        }

        return $image;
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by_id');
    }

    public function userRole(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }

    public function latestActivityLog(): MorphOne
    {
        return $this->morphOne(Activity::class, 'subject')->latestOfMany();
    }

    public function hasUserRole($userRoles): bool
    {
        if (! $this->userRole) {
            return false;
        }

        $userRoles = (array) $userRoles;

        return in_array(
            strtolower($this->userRole->name),
            array_map('strtolower', $userRoles)
        );
    }
}
