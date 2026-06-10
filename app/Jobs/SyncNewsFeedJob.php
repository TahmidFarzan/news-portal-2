<?php
namespace App\Jobs;

use App\Services\Cache\NewsCacheService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncNewsFeedJob implements ShouldQueue, ShouldBeUnique
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
        return "news-feed-sync-jobs-{$uqRandom}-{$currentTime}";
    }

    public function retryAfter()
    {
        return 60;
    }

    public function backoff()
    {
        return [61, 123, 185];
    }

    public function handle(NewsCacheService $newsCacheService): void
    {
        try {
            $filters = [];

            $lastPage = $newsCacheService->dbLastPageNo($filters);

            if ($lastPage > 0) {
                for ($page = 1; $page <= $lastPage; $page++) {
                    $newsCacheService->cachedNews('feed', ['page' => $page]);
                }
            }

            $newsCacheService->cachedNewsCount('feed', $filters);
            $newsCacheService->cachedLastPageNo('feed', $filters);
        } catch (Exception $exception) {
            Log::error('News feed sync job error: ' . $exception->getMessage(), [
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }
}
