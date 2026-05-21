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

class NewsContributorSyncJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $fail_limit = 3;

    public News $news;
    public $contributorIds;

    public function __construct(News $news, $contributorIds)
    {
        $this->news           = $news;
        $this->contributorIds = $contributorIds;
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $newTitleFormat = Str::slug($this->news->title);
        return "news-{$newTitleFormat}-contributor-sync-jobs" . Str::uuid()->toString() . Str::random(15) . '-' . time();
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
            $news = $this->news;
            DB::transaction(function () use ($news) {
                $news->contributors()->sync($this->contributorIds);
            });
        } catch (Exception $ex) {
            Log::error('News contributor sync job error: ' . $ex->getMessage());
        }
    }
}
