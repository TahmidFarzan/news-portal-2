<?php
namespace App\Jobs;

use App\Services\Cache\EventCacheService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncEventSitemapJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public EventCacheService $eventCacheService;

    public function __construct()
    {
        $this->eventCacheService = app(EventCacheService::class);
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $currentTime = time();
        $uqRandom    = Str::random(15);
        return "event-sitemap-sync-jobs-{$uqRandom}-{$currentTime}";
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

            $dbRecordCount     = $this->eventCacheService->dbEventsCount($filters);
            $cachedRecordTotal = $this->eventCacheService->eventsCount('sitemap', $filters);

            $dbLastPageNo     = $this->eventCacheService->dbLastPageNo($filters);
            $cachedLastPageNo = $this->eventCacheService->lastPageNo('sitemap', $filters);

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
                    $this->eventCacheService->cachedEvents(
                        'sitemap',
                        array_merge($filters, [
                            'page' => $page,
                        ])
                    );
                }
            }

            $this->eventCacheService->cachedEventsCount('sitemap', $filters);
            $this->eventCacheService->cachedLastPageNo('sitemap', $filters);
        } catch (Exception $ex) {
            Log::error('Event sitemap sync job error: ' . $ex->getMessage());

            throw $ex;
        }
    }
}
