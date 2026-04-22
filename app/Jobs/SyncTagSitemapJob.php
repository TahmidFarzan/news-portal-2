<?php
namespace App\Jobs;

use App\Services\Cache\TagCacheService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncTagSitemapJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public TagCacheService $tagCacheService;

    public function __construct()
    {
        $this->tagCacheService = app(TagCacheService::class);
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $currentTime = time();
        $uqRandom    = Str::random(15);
        return "tag-sitemap-sync-jobs-{$uqRandom}-{$currentTime}";
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
            $dbRecordCount     = $this->tagCacheService->dbRecordsCount();
            $cachedRecordTotal = $this->tagCacheService->recordsCount("sitemap");

            $dbLastPageNo     = $this->tagCacheService->dbLastPageNo(null);
            $cachedLastPageNo = $this->tagCacheService->lastPageNo("sitemap");

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
                    $this->tagCacheService->cachedRecords("sitemap", null, $page);
                }
            }

            $this->tagCacheService->cachedRecordsCount("sitemap");
            $this->tagCacheService->cachedLastPageNo("sitemap");
        } catch (Exception $ex) {
            Log::error('Tag sitemap sync job error: ' . $ex->getMessage());

            throw $ex;
        }
    }
}
