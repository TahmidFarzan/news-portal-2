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

class DeleteLocationRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $locationId;

    public function __construct(int $locationId)
    {
        $this->locationId = $locationId;
    }

    public function uniqueId(): string
    {
        return "delete-relations-location-{$this->locationId}";
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

        if ($location && ($location->activityLogs()->exists()) || ($location->stories()->exists())) {
            DB::beginTransaction();
            try {

                if ($location->activityLogs()->exists()) {
                    $location->activityLogs()->delete();
                }

                if ($location->stories()->exists()) {
                    $location->stories()->delete();
                }

                DB::commit();

            } catch (Exception $ex) {
                DB::rollback();

                Log::error("Fail to delete location relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
