<?php
namespace App\Jobs;

use App\Models\BreakingNews;
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

class NewsBreakingNewsSyncJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $fail_limit = 3;

    public News $news;
    public ?string $breakingNewsId;

    public function __construct(News $news, ?string $breakingNewsId)
    {
        $this->news           = $news;
        $this->breakingNewsId = $breakingNewsId;
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        return "news-{$this->news->slug}-breaking-news-sync-jobs" . Str::uuid()->toString() . Str::random(15) . '-' . time();
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
        $news           = $this->news;
        $breakingNewsId = $this->breakingNewsId;

        try {

            if ($breakingNewsId) {
                $breakingNews = BreakingNews::where("id", $breakingNewsId)->where("is_published", true)->first();

                if ($breakingNews) {
                    DB::transaction(function () use ($news, $breakingNews) {
                        $breakingNews->news_id = $news->id;
                        $breakingNews->save();
                    });
                }
            } else {
                $breakingNews = $news->breakingNews;
                DB::transaction(function () use ($breakingNews) {
                    $breakingNews->news_id = null;
                    $breakingNews->save();
                });

            }

        } catch (Exception $ex) {
            Log::error("Breaking news sync failed for {$news->title}.", [
                'exception' => $ex,
            ]);
        }
    }
}
