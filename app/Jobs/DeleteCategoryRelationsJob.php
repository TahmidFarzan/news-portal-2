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

class DeleteCategoryRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $categoryId;

    public function __construct(int $categoryId)
    {
        $this->categoryId = $categoryId;
    }

    public function uniqueId(): string
    {
        return "delete-relations-category-{$this->categoryId}";
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

        if ($category && ($category->activities()->exists())) {
            DB::beginTransaction();
            try {

                if ($category->activities()->exists()) {
                    $category->activities()->delete();
                }

                DB::commit();

            } catch (Exception $ex) {
                DB::rollback();

                Log::error("Fail to delete category relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
