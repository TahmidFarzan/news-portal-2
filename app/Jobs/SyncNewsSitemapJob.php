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

class SyncNewsSitemapJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public NewsCacheService $newsCacheService;

    public function __construct()
    {
        $this->newsCacheService = app(NewsCacheService::class);
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $currentTime = time();
        $uqRandom    = Str::random(15);
        return "news-sitemap-sync-jobs-{$uqRandom}-{$currentTime}";
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
            $key = 'sitemap';
            $filters = [];

            $lastPage = $this->newsCacheService->dbLastPageNo($filters);

            if ($lastPage > 0) {
                for ($page = 1; $page <= $lastPage; $page++) {
                    $this->newsCacheService->cachedNewses(['page' => $page,], $key);
                }
            }

            $this->newsCacheService->cachedNewsesCount($filters, $key);
            $this->newsCacheService->cachedLastPageNo($filters, $key);
        } catch (Exception $exception) {
            Log::error('News sitemap sync job error: ' . $exception->getMessage(), [
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }
}
