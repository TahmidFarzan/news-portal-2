<?php
namespace App\Jobs;

use App\Models\Page;
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

class DeletePageRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $pageId;

    public function __construct(int $pageId)
    {
        $this->pageId = $pageId;
    }

    public function uniqueId(): string
    {
        return "delete-page-{$this->pageId}-relations";
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
        $page = Page::find($this->pageId);

        if ($page && ($page->activityLogs()->exists())) {
            try {
                DB::transaction(function () use ($page) {
                    if ($page->activityLogs()->exists()) {
                        $page->activityLogs()->delete();
                    }
                });

            } catch (Exception $ex) {

                Log::error("Fail to delete page relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
