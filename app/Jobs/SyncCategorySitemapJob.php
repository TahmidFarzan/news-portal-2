<?php
namespace App\Jobs;

use App\Services\Cache\CategoryCacheService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncCategorySitemapJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public CategoryCacheService $categoryCacheService;

    public function __construct()
    {
        $this->categoryCacheService = app(CategoryCacheService::class);
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $currentTime = time();
        $uqRandom    = Str::random(15);
        return "category-sitemap-sync-jobs-{$uqRandom}-{$currentTime}";
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

            $dbRecordCount     = $this->categoryCacheService->dbCategoriesCount($filters);
            $cachedRecordTotal = $this->categoryCacheService->categoriesCount('sitemap', $filters);

            $dbLastPageNo     = $this->categoryCacheService->dbLastPageNo($filters);
            $cachedLastPageNo = $this->categoryCacheService->lastPageNo('sitemap', $filters);

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
                    $this->categoryCacheService->cachedCategories(
                        'sitemap',
                        array_merge($filters, [
                            'page' => $page,
                        ])
                    );
                }
            }

            $this->categoryCacheService->cachedCategoriesCount('sitemap', $filters);
            $this->categoryCacheService->cachedLastPageNo('sitemap', $filters);
        } catch (Exception $ex) {
            Log::error('Category sitemap sync job error: ' . $ex->getMessage());

            throw $ex;
        }
    }
}
