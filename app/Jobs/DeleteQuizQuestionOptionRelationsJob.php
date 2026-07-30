<?php
namespace App\Jobs;

use App\Models\QuizQuestionOption;
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

class DeleteQuizQuestionOptionRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $quizQuestionOptionId;

    public function __construct(int $quizQuestionOptionId)
    {
        $this->quizQuestionOptionId = $quizQuestionOptionId;
    }

    public function uniqueId(): string
    {
        return "delete-quiz-{$this->quizQuestionOptionId}-relations";
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
        $quizQuestionOption = QuizQuestionOption::find($this->quizQuestionOptionId);

        if ($quizQuestionOption && ($quizQuestionOption->activityLogs()->exists()) ) {
            try {

                DB::transaction(function () use ($quizQuestionOption) {
                    if ($quizQuestionOption->activityLogs()->exists()) {
                        $quizQuestionOption->activityLogs()->delete();
                    }

                });

            } catch (Exception $ex) {

                Log::error("Fail to delete quiz question option relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
