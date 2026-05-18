<?php
namespace App\Jobs;

use App\Models\Menu;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteMenuRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $menuId;

    public function __construct(int $menuId)
    {
        $this->menuId = $menuId;
    }

    public function uniqueId(): string
    {
        return "delete-relations-menu-{$this->menuId}";
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
        $menu = Menu::find($this->menuId);

        if ($menu && ($menu->activityLogs()->exists()) || ($menu->menuItems()->exists()) ) {
            DB::beginTransaction();
            try {

                if ($menu->activityLogs()->exists()) {
                    $menu->activityLogs()->delete();
                }

                if ($menu->menuItems()->exists()) {
                    $menu->menuItems()->delete();
                }

                DB::commit();

            } catch (Exception $ex) {
                DB::rollback();

                Log::error("Fail to delete menu relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
