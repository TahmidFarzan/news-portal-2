<?php
namespace App\Observers;


use App\Models\Location;
use Illuminate\Support\Str;
use App\Jobs\DeleteLocationRelationsJob;

class LocationObserver
{
    public function creating(Location $location): void
    {
        $this->treeUpdate($location);
    }

    public function updating(Location $location): void
    {
        $this->treeUpdate($location);
    }

    public function deleting(Location $location): void
    {
        DeleteLocationRelationsJob::dispatchSync($location->id);
    }

    private function treeUpdate(Location $location)
    {
        $name = $location->name;
        $slug = $location->slug ?? Str::lower($location->name);

        $nameTree = $name;
        $slugTree = $slug;
        if ($location->parent_id) {
            if (! $location->relationLoaded('parent')) {
                $location->load('parent');
            }
            $slugTree = "{$location->parent?->slug_tree}/{$slugTree}";
            $nameTree = "{$location->parent?->name_tree} - {$nameTree}";
        }
        $location->slug_tree = $slugTree;
        $location->name_tree = $nameTree;
    }
}
