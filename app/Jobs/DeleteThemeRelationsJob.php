<?php
namespace App\Jobs;

use App\Models\Theme;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteThemeRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $themeId;

    public function __construct(int $themeId)
    {
        $this->themeId = $themeId;
    }

    public function uniqueId(): string
    {
        return "delete-theme-{$this->themeId}-relations";
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
        $theme = Theme::find($this->themeId);

        if ($theme && ($theme->activityLogs()->exists())) {
            try {

                DB::transaction(function () use ($theme) {
                    if ($theme->activityLogs()->exists()) {
                        $theme->activityLogs()->delete();
                    }
                });

            } catch (Exception $ex) {
                Log::error("Fail to delete theme relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
