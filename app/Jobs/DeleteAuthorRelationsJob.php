<?php
namespace App\Jobs;

use App\Models\Author;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteAuthorRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $authorId;

    public function __construct(int $authorId)
    {
        $this->authorId = $authorId;
    }

    public function uniqueId(): string
    {
        return "delete-relations-author-{$this->authorId}";
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
        $author = Author::find($this->authorId);

        if ($author && ($author->activityLogs()->exists()) || ($author->trend)) {
            DB::beginTransaction();
            try {

                if ($author->activityLogs()->exists()) {
                    $author->activityLogs()->delete();
                }

                if ($author->trend) {
                    $author->trend->delete();
                }

                DB::commit();

            } catch (Exception $ex) {
                DB::rollback();

                Log::error("Fail to delete author relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
