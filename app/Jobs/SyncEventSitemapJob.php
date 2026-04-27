<?php
namespace App\Jobs;

use App\Services\Cache\EventCacheService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncEventSitemapJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public EventCacheService $eventCacheService;

    public function __construct()
    {
        $this->eventCacheService = app(EventCacheService::class);
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $currentTime = time();
        $uqRandom    = Str::random(15);
        return "event-sitemap-sync-jobs-{$uqRandom}-{$currentTime}";
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
            $dbRecordCount     = $this->eventCacheService->dbRecordsCount();
            $cachedRecordTotal = $this->eventCacheService->recordsCount("sitemap");

            $dbLastPageNo     = $this->eventCacheService->dbLastPageNo(null);
            $cachedLastPageNo = $this->eventCacheService->lastPageNo("sitemap");

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
                    $this->eventCacheService->cachedRecords("sitemap", null, $page);
                }
            }

            $this->eventCacheService->cachedRecordsCount("sitemap");
            $this->eventCacheService->cachedLastPageNo("sitemap");
        } catch (Exception $ex) {
            Log::error('Event sitemap sync job error: ' . $ex->getMessage());

            throw $ex;
        }
    }
}
