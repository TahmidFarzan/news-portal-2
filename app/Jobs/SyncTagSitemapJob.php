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

    public function handle(TagCacheService $tagCacheService): void
    {
        try {
            $filters = [];

            $dbRecordCount     = $tagCacheService->dbTagsCount($filters);
            $cachedRecordTotal = $tagCacheService->tagsCount('sitemap', $filters);

            $dbLastPageNo     = $tagCacheService->dbLastPageNo($filters);
            $cachedLastPageNo = $tagCacheService->lastPageNo('sitemap', $filters);

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
                    $tagCacheService->cachedTags(
                        'sitemap',
                        array_merge($filters, [
                            'page' => $page,
                        ])
                    );
                }
            }

            $tagCacheService->cachedTagsCount('sitemap', $filters);
            $tagCacheService->cachedLastPageNo('sitemap', $filters);
        } catch (Exception $ex) {
            Log::error('Tag sitemap sync job error: ' . $ex->getMessage());

            throw $ex;
        }
    }
}
