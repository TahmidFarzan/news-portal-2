<?php
namespace App\Jobs;

use App\Models\QuizQuestion;
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

class DeleteQuizQuestionRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $quizQuestionId;

    public function __construct(int $quizQuestionId)
    {
        $this->quizQuestionId = $quizQuestionId;
    }

    public function uniqueId(): string
    {
        return "delete-quiz-{$this->quizQuestionId}-relations";
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
        $quizQuestion = QuizQuestion::find($this->quizQuestionId);

        if ($quizQuestion && ($quizQuestion->activityLogs()->exists()) || ($quizQuestion->quizQuestionOptions()->exists() > 0) ) {
            try {

                DB::transaction(function () use ($quizQuestion) {
                    if ($quizQuestion->activityLogs()->exists()) {
                        $quizQuestion->activityLogs()->delete();
                    }

                    if ($quizQuestion->quizQuestionOptions()->exists()) {
                        $quizQuestion->quizQuestionOptions()->delete();
                    }

                });

            } catch (Exception $ex) {

                Log::error("Fail to delete quiz question relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
