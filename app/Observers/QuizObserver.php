<?php
namespace App\Observers;

use App\Models\Quiz;
use Illuminate\Support\Str;
use App\Jobs\DeleteQuizRelationsJob;

class QuizObserver
{
    public function deleting(Quiz $quiz): void
    {
        DeleteQuizRelationsJob::dispatchSync($quiz->id);
    }


}
