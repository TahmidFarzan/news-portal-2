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

class DeleteNewsRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $newsId;

    public function __construct(int $newsId)
    {
        $this->newsId = $newsId;
    }

    public function uniqueId(): string
    {
        return "delete-news-{$this->newsId}-relations";
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
        $news = News::find($this->newsId);

        if ($news && ($news->activityLogs()->exists()) || ($news->getMedia($news->media_collection_name)->count() > 0) || $news->tags()->exists() || $news->contributors()->exists() || $news->relevantNews()->exists() || $news->relatedNews()->exists() || $news->breakingNews()->exists()) {

            try {

                DB::transaction(function () use ($news) {
                    if ($news->activityLogs()->exists()) {
                        $news->activityLogs()->delete();
                    }

                    if ($news->getMedia($news->media_collection_name)->count() > 0) {
                        $news->clearMediaCollection($news->media_collection_name);
                    }

                    if ($news->tags()->exists()) {
                        $news->tags()->delete();
                    }

                    if ($news->contributors()->exists()) {
                        $news->contributors()->delete();
                    }

                    if ($news->relevantNews()->exists()) {
                        $news->relevantNews()->delete();
                    }

                    if ($news->relatedNews()->exists()) {
                        $news->relatedNews()->delete();
                    }

                    if ($news->breakingNews()->exists()) {
                        $news->breakingNews()->delete();
                    }
                });

            } catch (Exception $ex) {

                Log::error("Fail to delete news relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
