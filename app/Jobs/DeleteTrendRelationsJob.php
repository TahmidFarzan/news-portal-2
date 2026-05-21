<?php
namespace App\Jobs;

use App\Models\Trend;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteTrendRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $trendId;

    public function __construct(int $trendId)
    {
        $this->trendId = $trendId;
    }

    public function uniqueId(): string
    {
        return "delete-relations-trend-{$this->trendId}";
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
        $trend = Trend::find($this->trendId);

        if ($trend && ($trend->activityLogs()->exists())) {
            try {

                DB::transaction(function () use ($trend) {
                    if ($trend->activityLogs()->exists()) {
                        $trend->activityLogs()->delete();
                    }
                });

            } catch (Exception $ex) {
                Log::error("Fail to delete trend relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
