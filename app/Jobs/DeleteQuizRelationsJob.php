<?php
namespace App\Jobs;

use App\Models\Quiz;
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

class DeleteQuizRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public int $quizId;

    public function __construct(int $quizId)
    {
        $this->quizId = $quizId;
    }

    public function uniqueId(): string
    {
        return "delete-quiz-{$this->quizId}-relations";
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
        $quiz = Quiz::find($this->quizId);

        if ($quiz && ($quiz->activityLogs()->exists()) || ($quiz->quizQuestions()->exists() > 0) ) {
            try {

                DB::transaction(function () use ($quiz) {
                    if ($quiz->activityLogs()->exists()) {
                        $quiz->activityLogs()->delete();
                    }

                    if ($quiz->quizQuestions()->exists()) {
                        $quiz->quizQuestions()->delete();
                    }

                });

            } catch (Exception $ex) {

                Log::error("Fail to delete quiz relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
