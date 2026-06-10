<?php
namespace App\Jobs;

use App\Services\Cache\ContributorCacheService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncContributorSitemapJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $currentTime = time();
        $uqRandom    = Str::random(15);
        return "contributor-sitemap-sync-jobs-{$uqRandom}-{$currentTime}";
    }

    public function retryAfter()
    {
        return 60;
    }

    public function backoff()
    {
        return [61, 123, 185];
    }

    public function handle(ContributorCacheService $contributorCacheService): void
    {
        try {
            $filters = [];

            $dbRecordCount     = $contributorCacheService->dbContributorsCount($filters);
            $cachedRecordTotal = $contributorCacheService->contributorsCount('sitemap', $filters);

            $dbLastPageNo     = $contributorCacheService->dbLastPageNo($filters);
            $cachedLastPageNo = $contributorCacheService->lastPageNo('sitemap', $filters);

            if ($cachedRecordTotal !== $dbRecordCount) {
                $pageStart = $cachedLastPageNo;

                if ($pageStart > 1) {
                    $pageStart--;
                }

                $pageEnd = $cachedLastPageNo;

                if ($cachedLastPageNo < $dbLastPageNo) {
                    $pageStart = $cachedLastPageNo;
                    $pageEnd   = $dbLastPageNo;
                }

                foreach (range($pageStart, $pageEnd) as $page) {
                    $contributorCacheService->cachedContributors(
                        'sitemap',
                        array_merge($filters, [
                            'page' => $page,
                        ])
                    );
                }
            }

            $contributorCacheService->cachedContributorsCount('sitemap', $filters);
            $contributorCacheService->cachedLastPageNo('sitemap', $filters);
        } catch (Exception $ex) {
            Log::error('Contributor sitemap sync job error: ' . $ex->getMessage());

            throw $ex;
        }
    }
}
