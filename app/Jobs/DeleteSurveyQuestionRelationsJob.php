<?php
namespace App\Jobs;

use App\Models\SurveyQuestion;
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

class DeleteSurveyQuestionRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $surveyQuestionId;

    public function __construct(int $surveyQuestionId)
    {
        $this->surveyQuestionId = $surveyQuestionId;
    }

    public function uniqueId(): string
    {
        return "delete-survey-question-{$this->surveyQuestionId}-relations";
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
        $surveyQuestion = SurveyQuestion::find($this->surveyQuestionId);

        if ($surveyQuestion && ($surveyQuestion->activityLogs()->exists()) ($surveyQuestion->surveyQuestionResult()->exists())) {

            try {

                DB::transaction(function () use ($surveyQuestion) {
                    if ($surveyQuestion->activityLogs()->exists()) {
                        $surveyQuestion->activityLogs()->delete();
                    }

                    if ($surveyQuestion->surveyQuestionResult()->exists()) {
                        $surveyQuestion->surveyQuestionResult()->delete();
                    }
                });

            } catch (Exception $ex) {
                Log::error("Fail to delete survey question relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
