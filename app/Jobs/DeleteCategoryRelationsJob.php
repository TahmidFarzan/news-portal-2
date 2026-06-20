<?php
namespace App\Jobs;

use App\Models\Category;
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

class DeleteCategoryRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $categoryId;

    public function __construct(int $categoryId)
    {
        $this->categoryId = $categoryId;
    }

    public function uniqueId(): string
    {
        return "delete-category-{$this->categoryId}-relations";
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
        $category = Category::find($this->categoryId);

        if ($category && ($category->activityLogs()->exists()) || ($category->locations()->exists()) || ($category->news()->exists())) {
            try {
                DB::transaction(function () use ($category) {
                    if ($category->activityLogs()->exists()) {
                        $category->activityLogs()->delete();
                    }

                    if ($category->locations()->exists()) {
                        $category->locations()->delete();
                    }

                    if ($category->news()->exists()) {
                        $category->news()->delete();
                    }
                });

            } catch (Exception $ex) {

                Log::error("Fail to delete category relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
