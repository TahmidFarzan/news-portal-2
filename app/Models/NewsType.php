<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Table('news_types')]
#[Fillable(['name', 'slug'])]
class NewsType extends Model
{
    use HasFactory, HasSlug;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->saveSlugsTo('slug')
            ->generateSlugsFrom(function ($model) {
                $mainSlug     = $model->name;
                $randomString = Str::random(11);
                $createdAt    = $model->created_at ?? now();
                $createdAt    = $createdAt->format('HisdmY');
                return "{$mainSlug}-{$randomString}-{$createdAt}";
            })
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(255);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
