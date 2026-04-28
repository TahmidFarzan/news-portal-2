<?php
namespace App\Jobs;

use App\Services\Cache\AuthorCacheService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncAuthorSitemapJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public AuthorCacheService $authorCacheService;

    public function __construct()
    {
        $this->authorCacheService = app(AuthorCacheService::class);
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $currentTime = time();
        $uqRandom    = Str::random(15);
        return "author-sitemap-sync-jobs-{$uqRandom}-{$currentTime}";
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
            $dbRecordCount     = $this->authorCacheService->dbRecordsCount();
            $cachedRecordTotal = $this->authorCacheService->recordsCount("sitemap");

            $dbLastPageNo     = $this->authorCacheService->dbLastPageNo(null);
            $cachedLastPageNo = $this->authorCacheService->lastPageNo("sitemap");

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
                    $this->authorCacheService->cachedRecords("sitemap", null, $page);
                }
            }

            $this->authorCacheService->cachedRecordsCount("sitemap");
            $this->authorCacheService->cachedLastPageNo("sitemap");
        } catch (Exception $ex) {
            Log::error('Author sitemap sync job error: ' . $ex->getMessage());

            throw $ex;
        }
    }
}
