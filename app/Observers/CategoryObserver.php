<?php
namespace App\Observers;

use App\Jobs\SyncCategorySitemapJob;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Jobs\DeleteCategoryRelationsJob;

class CategoryObserver
{
    public function creating(Category $category): void
    {
        $this->treeUpdate($category);
    }

    public function updating(Category $category): void
    {
        $this->treeUpdate($category);
    }

    public function deleting(Category $category): void
    {
        DeleteCategoryRelationsJob::dispatchSync($category->id);
    }

    public function created(Category $category): void
    {
        SyncCategorySitemapJob::dispatch();
    }

    public function deleted(Category $category): void
    {
        SyncCategorySitemapJob::dispatch();
    }

    private function treeUpdate(Category $category)
    {
        $name = $category->name;
        $slug = Str::slug($category->name);

        $nameTree = $name;
        $slugTree = $slug;
        if ($category->parent_id) {
            if (! $category->relationLoaded('parent')) {
                $category->load('parent');
            }
            $slugTree = "{$category->parent->slug_tree}/{$slugTree}";
            $nameTree = "{$category->parent->name_tree} - {$nameTree}";
        }
        $category->slug_tree = $slugTree;
        $category->name_tree = $nameTree;
    }
}
