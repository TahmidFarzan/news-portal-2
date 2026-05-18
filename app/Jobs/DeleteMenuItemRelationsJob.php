<?php

namespace App\Jobs;

use App\Models\MenuItem;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteMenuItemRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $menuItemId;

    public function __construct(int $menuItemId)
    {
        $this->menuItemId = $menuItemId;
    }

    public function uniqueId(): string
    {
        return "delete-relations-menu-item-{$this->menuItemId}";
    }

    public function retryAfter()
    {
        return 60;
    }

    public function backoff()
    {
        return [61, 123, 185];
    }

    public function handle(): void
    {
        $menuItem = MenuItem::find($this->menuItemId);

        if ($menuItem && ($menuItem->activityLogs()->exists())  ) {
            DB::beginTransaction();
            try {

                if ($menuItem->activityLogs()->exists()) {
                    $menuItem->activityLogs()->delete();
                }

                DB::commit();

            } catch (Exception $ex) {
                DB::rollback();

                Log::error("Fail to delete menu item relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
