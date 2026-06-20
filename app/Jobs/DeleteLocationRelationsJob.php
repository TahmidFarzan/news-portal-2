<?php
namespace App\Jobs;

use App\Models\Location;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class DeleteLocationRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $locationId;

    public function __construct(int $locationId)
    {
        $this->locationId = $locationId;
    }

    public function uniqueId(): string
    {
        return "delete-location-{$this->locationId}-relations";
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
        $location = Location::find($this->locationId);

        if ($location && ($location->activityLogs()->exists()) || ($location->news()->exists())) {
            try {

                DB::transaction(function () use ($location) {
                    if ($location->activityLogs()->exists()) {
                        $location->activityLogs()->delete();
                    }

                    if ($location->news()->exists()) {
                        $location->news()->delete();
                    }
                });

            } catch (Exception $ex) {

                Log::error("Fail to delete location relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
