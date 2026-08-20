<?php
namespace App\Jobs;

use App\Helpers\CacheHelper;
use App\Services\FeedService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class LatestNewsFeedCacheClearJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $currentTime = time();
        $uqRandom    = Str::random(15);
        return "latest-news-feed-cached-clear-jobs-{$uqRandom}-{$currentTime}";
    }

    public function retryAfter()
    {
        return 60;
    }

    public function backoff()
    {
        return [61, 123, 185];
    }

    public function handle(FeedService $feedService): void
    {
        try {
            $feedService->clearLatestNews();
        } catch (Exception $ex) {
            Log::error('Latest news feed cached clear job error: ' . $ex->getMessage());
        }
    }
}
