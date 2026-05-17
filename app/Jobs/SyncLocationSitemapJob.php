<?php
namespace App\Jobs;

use App\Services\Cache\LocationCacheService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncLocationSitemapJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public LocationCacheService $locationCacheService;

    public function __construct()
    {
        $this->locationCacheService = app(LocationCacheService::class);
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $currentTime = time();
        $uqRandom    = Str::random(15);
        return "location-sitemap-sync-jobs-{$uqRandom}-{$currentTime}";
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

            $dbRecordCount     = $this->locationCacheService->dbLocationsCount($filters);
            $cachedRecordTotal = $this->locationCacheService->locationsCount('sitemap', $filters);

            $dbLastPageNo     = $this->locationCacheService->dbLastPageNo($filters);
            $cachedLastPageNo = $this->locationCacheService->lastPageNo('sitemap', $filters);

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
                    $this->locationCacheService->cachedLocations(
                        'sitemap',
                        array_merge($filters, [
                            'page' => $page,
                        ])
                    );
                }
            }

            $this->locationCacheService->cachedLocationsCount('sitemap', $filters);
            $this->locationCacheService->cachedLastPageNo('sitemap', $filters);
        } catch (Exception $ex) {
            Log::error('Location sitemap sync job error: ' . $ex->getMessage());

            throw $ex;
        }
    }
}
