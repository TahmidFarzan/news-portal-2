<?php
namespace App\Models;

use App\Policies\ActivityLogPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

#
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity as BaseActivity;

#
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[UsePolicy(ActivityLogPolicy::class)]
class ActivityLog extends BaseActivity
{
    use HasSlug;

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
}
