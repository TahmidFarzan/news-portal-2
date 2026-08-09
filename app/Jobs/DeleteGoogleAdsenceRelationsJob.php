<?php
namespace App\Jobs;

use App\Models\GoogleAdsense;
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

class DeleteGoogleAdsenseRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $googleAdsenseId;

    public function __construct(int $googleAdsenseId)
    {
        $this->googleAdsenseId = $googleAdsenseId;
    }

    public function uniqueId(): string
    {
        return "delete-google_adsense-relations-{$this->googleAdsenseId}";
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
        $googleAdsense = GoogleAdsense::find($this->googleAdsenseId);

        if ($googleAdsense && ($googleAdsense->activities()->exists())) {

            try {

                DB::transaction(function () use ($googleAdsense) {
                    if ($googleAdsense->activities()->exists()) {
                        $googleAdsense->activities()->delete();
                    }


                });
            } catch (Exception $ex) {

                Log::error("Fail to cleanup google adsense relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
