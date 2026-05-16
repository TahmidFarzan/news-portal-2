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
        return "delete-relations-news-{$this->newsId}";
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

        if ($news && ($news->activityLogs()->exists()) || ($news->getMedia($news->media_collection_name)->count() > 0) || $news->tags()->exists() || $news->contributors()->exists() || $news->relevantNewses()->exists() || $news->relatedNewses()->exists()) {
            DB::beginTransaction();
            try {

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

                if ($news->relevantNewses()->exists()) {
                    $news->relevantNewses()->delete();
                }

                if ($news->relatedNewses()->exists()) {
                    $news->relatedNewses()->delete();
                }

                DB::commit();

            } catch (Exception $ex) {
                DB::rollback();

                Log::error("Fail to delete news relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
