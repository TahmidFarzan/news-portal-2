<?php
namespace App\Observers;

use App\Jobs\DeletePageRelationsJob;

use App\Models\Page;
use Illuminate\Support\Str;

class PageObserver
{
    public function creating(Page $page): void
    {
        $this->treeUpdate($page);
    }

    public function updating(Page $page): void
    {
        $this->treeUpdate($page);
    }

    public function deleting(Page $page): void
    {
        DeletePageRelationsJob::dispatchSync($page->id);
    }


    private function treeUpdate(Page $page)
    {
        $title = $page->title;
        $slug = $page->slug ?? Str::lower($page->title);

        $titleTree = $title;
        $slugTree  = $slug;
        if ($page->parent_id) {
            if (! $page->relationLoaded('parent')) {
                $page->load('parent');
            }
            $slugTree  = "{$page->parent->slug_tree}/{$slugTree}";
            $titleTree = "{$page->parent->title_tree} - {$titleTree}";
        }
        $page->slug_tree  = $slugTree;
        $page->title_tree = $titleTree;
    }
}
