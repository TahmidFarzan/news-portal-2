<?php
namespace App\Jobs;

use App\Models\Setting;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteSettingRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $settingId;

    public function __construct(int $settingId)
    {
        $this->settingId = $settingId;
    }

    public function uniqueId(): string
    {
        return "delete-setting-{$this->settingId}-relations";
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
        $setting = Setting::find($this->settingId);

        if ($setting && ($setting->activityLogs()->exists())) {
            try {

                DB::transaction(function () use ($setting) {
                    if ($setting->activityLogs()->exists()) {
                        $setting->activityLogs()->delete();
                    }
                });

            } catch (Exception $ex) {
                Log::error("Fail to delete setting relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
