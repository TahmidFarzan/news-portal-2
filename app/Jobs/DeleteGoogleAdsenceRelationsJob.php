<?php
namespace App\Jobs;

use App\Models\GoogleAdsence;
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

class DeleteGoogleAdsenceRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $googleAdsenceId;

    public function __construct(int $googleAdsenceId)
    {
        $this->googleAdsenceId = $googleAdsenceId;
    }

    public function uniqueId(): string
    {
        return "delete-google_adsence-relations-{$this->googleAdsenceId}";
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
        $googleAdsence = GoogleAdsence::find($this->googleAdsenceId);

        if ($googleAdsence && ($googleAdsence->activities()->exists())) {

            try {

                DB::transaction(function () use ($googleAdsence) {
                    if ($googleAdsence->activities()->exists()) {
                        $googleAdsence->activities()->delete();
                    }


                });
            } catch (Exception $ex) {

                Log::error("Fail to cleanup google adsence relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
