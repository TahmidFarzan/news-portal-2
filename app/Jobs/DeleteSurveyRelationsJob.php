<?php
namespace App\Jobs;

use App\Models\Survey;
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

class DeleteSurveyRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $surveyId;

    public function __construct(int $surveyId)
    {
        $this->surveyId = $surveyId;
    }

    public function uniqueId(): string
    {
        return "delete-survey-{$this->surveyId}-relations";
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
        $survey = Survey::find($this->surveyId);

        if ($survey && ($survey->activityLogs()->exists()) || $survey->surveyQuestions()->exists()) {

            try {

                DB::transaction(function () use ($survey) {
                    if ($survey->activityLogs()->exists()) {
                        $survey->activityLogs()->delete();
                    }

                    if ($survey->surveyQuestions()->exists()) {
                        $survey->surveyQuestions()->delete();
                    }
                });

            } catch (Exception $ex) {
                Log::error("Fail to delete survey relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
