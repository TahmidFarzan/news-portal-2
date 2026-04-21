<?php
namespace App\Jobs;

use Exception;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteUserRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function uniqueId(): string
    {
        return "delete-relations-user-{$this->userId}";
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
        $user = User::find($this->userId);

        if ($user && ($user->activityLogs()->exists() || ($user->getMedia($user->media_collection_name)->count() > 0))) {
            DB::beginTransaction();
            try {

                if ($user->activityLogs()->exists()) {
                    $user->activityLogs()->delete();
                }

                if ($user->getMedia($user->media_collection_name)->count() > 0) {
                    $user->clearMediaCollection($user->media_collection_name);
                }

                DB::commit();

            } catch (Exception $ex) {
                DB::rollback();

                Log::error("Fail to delete user relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
