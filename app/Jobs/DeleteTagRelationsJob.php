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

class DeleteTagRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tagId;

    public function __construct(int $tagId)
    {
        $this->tagId = $tagId;
    }

    public function uniqueId(): string
    {
        return "delete-relations-tag-{$this->tagId}";
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
        $tag = Tag::find($this->tagId);

        if ($tag && ($tag->activityLogs()->exists()) || ($tag->trend) || $tag->newses()->exists()) {

            try {

                DB::transaction(function () use ($tag) {
                    if ($tag->activityLogs()->exists()) {
                        $tag->activityLogs()->delete();
                    }

                    if ($tag->trend) {
                        $tag->trend->delete();
                    }

                    if ($tag->newses()->exists()) {
                        $tag->newses()->delete();
                    }
                });

            } catch (Exception $ex) {
                Log::error("Fail to delete tag relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
