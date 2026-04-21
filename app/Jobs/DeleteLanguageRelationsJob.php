<?php
namespace App\Jobs;

use App\Models\Language;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteLanguageRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $languageId;

    public function __construct(int $languageId)
    {
        $this->languageId = $languageId;
    }

    public function uniqueId(): string
    {
        return "delete-relations-language-{$this->languageId}";
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
        $language = Language::find($this->languageId);

        if ($language && ($language->activityLogs()->exists()) || ($language->categories()->exists())) {
            DB::beginTransaction();
            try {

                if ($language->activityLogs()->exists()) {
                    $language->activityLogs()->delete();
                }

                if ($language->categories()->exists()) {
                    $language->categories()->delete();
                }

                DB::commit();

            } catch (Exception $ex) {
                DB::rollback();

                Log::error("Fail to delete language relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
