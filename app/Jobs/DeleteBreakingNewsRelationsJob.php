<?php
namespace App\Jobs;

use App\Models\Tag;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class DeleteBreakingNewsRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $breakingNewsId;

    public function __construct(int $breakingNewsId)
    {
        $this->breakingNewsId = $breakingNewsId;
    }

    public function uniqueId(): string
    {
        return "delete-breaking-news-{$this->breakingNewsId}-relations";
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
        $breakingNews = Tag::find($this->breakingNewsId);

        if ($breakingNews && ($breakingNews->activityLogs()->exists())) {

            try {

                DB::transaction(function () use ($breakingNews) {
                    if ($breakingNews->activityLogs()->exists()) {
                        $breakingNews->activityLogs()->delete();
                    }
                });

            } catch (Exception $ex) {
                Log::error("Fail to delete breaking news relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
