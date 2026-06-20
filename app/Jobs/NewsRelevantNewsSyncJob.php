<?php
namespace App\Jobs;

use App\Models\News;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class NewsRelevantNewsSyncJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public $fail_limit = 3;

    public News $news;
    public $relevantIds;

    public function __construct(News $news, $relevantIds)
    {
        $this->news        = $news;
        $this->relevantIds = $relevantIds;
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $newTitleFormat = Str::slug($this->news->title);
        return "news-{$newTitleFormat}-relevant-news-sync-jobs" . Str::uuid()->toString() . Str::random(15) . '-' . time();
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
            $news        = $this->news;
            $relevantIds = $this->relevantIds;

            DB::transaction(function () use ($news, $relevantIds) {
                $news->relevantNews()->sync($relevantIds);
            });

        } catch (Exception $ex) {
            Log::error('News relevant news sync job error: ' . $ex->getMessage());
        }
    }
}
