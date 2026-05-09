<?php
namespace App\Jobs;

use App\Services\Cache\StoryCacheService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncStorySitemapJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public StoryCacheService $storyCacheService;

    public function __construct()
    {
        $this->storyCacheService = app(StoryCacheService::class);
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $currentTime = time();
        $uqRandom    = Str::random(15);
        return "story-sitemap-sync-jobs-{$uqRandom}-{$currentTime}";
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
        try {
            $dbRecordCount     = $this->storyCacheService->dbRecordsCount();
            $cachedRecordTotal = $this->storyCacheService->recordsCount("sitemap");

            $dbLastPageNo     = $this->storyCacheService->dbLastPageNo(null);
            $cachedLastPageNo = $this->storyCacheService->lastPageNo("sitemap");

            if (! ($cachedRecordTotal == $dbRecordCount)) {
                $pageStart = $cachedLastPageNo;
                if (! ($pageStart == null) && ($pageStart > 1)) {
                    $pageStart = $pageStart - 1;
                }
                $pageEnd = $cachedLastPageNo;

                if ($cachedLastPageNo < $dbLastPageNo) {
                    $pageStart = $cachedLastPageNo;
                    $pageEnd   = $dbLastPageNo;
                }

                foreach (range($pageStart, $pageEnd) as $page) {
                    $this->storyCacheService->cachedRecords("sitemap", null, $page);
                }
            }

            $this->storyCacheService->cachedRecordsCount("sitemap");
            $this->storyCacheService->cachedLastPageNo("sitemap");
        } catch (Exception $ex) {
            Log::error('Story sitemap sync job error: ' . $ex->getMessage());

            throw $ex;
        }
    }
}
