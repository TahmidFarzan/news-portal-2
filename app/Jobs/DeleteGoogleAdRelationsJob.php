<?php
namespace App\Jobs;

use App\Models\GoogleAd;
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

class DeleteGoogleAdRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $googleAdId;

    public function __construct(int $googleAdId)
    {
        $this->googleAdId = $googleAdId;
    }

    public function uniqueId(): string
    {
        return "delete-google_ad-relations-{$this->googleAdId}";
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
        $googleAd = GoogleAd::find($this->googleAdId);

        if ($googleAd && ($googleAd->activities()->exists())) {

            try {

                DB::transaction(function () use ($googleAd) {
                    if ($googleAd->activities()->exists()) {
                        $googleAd->activities()->delete();
                    }


                });
            } catch (Exception $ex) {

                Log::error("Fail to cleanup google ad relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
