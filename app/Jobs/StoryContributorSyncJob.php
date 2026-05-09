<?php
namespace App\Jobs;

use App\Models\Story;
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

class StoryContributorSyncJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $fail_limit = 3;

    public Story $story;
    public $contributorIds;

    public function __construct(Story $story, $contributorIds)
    {
        $this->story     = $story;
        $this->contributorIds     = $contributorIds;
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        $newTitleFormat = Str::slug($this->story->title);
        return "story-{$newTitleFormat}-contributor-sync-jobs" . Str::uuid()->toString() . Str::random(15) . '-' . time();
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
        DB::beginTransaction();
        try {
            $this->story->contributors()->sync($this->contributorIds);
            DB::commit();
        } catch (Exception $ex) {
            DB::rollback();
            Log::error('Story contributor sync job error: ' . $ex->getMessage());
        }
    }
}
