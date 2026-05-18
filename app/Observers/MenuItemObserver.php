<?php

namespace App\Observers;

use App\Models\MenuItem;
use Illuminate\Support\Str;
use App\Jobs\DeleteMenuItemRelationsJob;

class MenuItemObserver
{
    public function creating(MenuItem $menuItem): void
    {
        $this->treeUpdate($menuItem);
    }

    public function updating(MenuItem $menuItem): void
    {
        $this->treeUpdate($menuItem);
    }

    private function treeUpdate(MenuItem $menuItem)
    {
        $name = $menuItem->name;
        $slug = Str::slug($menuItem->name);

        $nameTree = $name;
        $slugTree = $slug;
        if ($menuItem->parent_id) {
            if (! $menuItem->relationLoaded('parent')) {
                $menuItem->load('parent');
            }
            $slugTree = "{$menuItem->parent->slug_tree}/{$slugTree}";
            $nameTree = "{$menuItem->parent->name_tree} - {$nameTree}";
        }
        $menuItem->slug_tree = $slugTree;
        $menuItem->name_tree = $nameTree;
    }
}
