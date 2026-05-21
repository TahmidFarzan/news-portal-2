<?php
namespace App\Jobs;

use App\Models\Contributor;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteContributorRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $contributorId;

    public function __construct(int $contributorId)
    {
        $this->contributorId = $contributorId;
    }

    public function uniqueId(): string
    {
        return "delete-relations-contributor-{$this->contributorId}";
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
        $contributor = Contributor::find($this->contributorId);

        if ($contributor && ($contributor->activityLogs()->exists()) || ($contributor->newses()->exists())) {

            try {

                DB::transaction(function () use ($contributor) {
                    if ($contributor->activityLogs()->exists()) {
                        $contributor->activityLogs()->delete();
                    }

                    if ($contributor->newses()->exists()) {
                        $contributor->newses()->delete();
                    }
                });


            } catch (Exception $ex) {

                Log::error("Fail to delete contributor relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
