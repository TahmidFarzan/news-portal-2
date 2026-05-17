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

    public ContributorCacheService $contributorCacheService;

    public function __construct()
    {
        $this->contributorCacheService = app(ContributorCacheService::class);
    }

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

    public function handle(): void
    {
        try {
            $filters = [];

            $dbRecordCount     = $this->contributorCacheService->dbContributorsCount($filters);
            $cachedRecordTotal = $this->contributorCacheService->contributorsCount('sitemap', $filters);

            $dbLastPageNo     = $this->contributorCacheService->dbLastPageNo($filters);
            $cachedLastPageNo = $this->contributorCacheService->lastPageNo('sitemap', $filters);

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
                    $this->contributorCacheService->cachedContributors(
                        'sitemap',
                        array_merge($filters, [
                            'page' => $page,
                        ])
                    );
                }
            }

            $this->contributorCacheService->cachedContributorsCount('sitemap', $filters);
            $this->contributorCacheService->cachedLastPageNo('sitemap', $filters);
        } catch (Exception $ex) {
            Log::error('Contributor sitemap sync job error: ' . $ex->getMessage());

            throw $ex;
        }
    }
}
