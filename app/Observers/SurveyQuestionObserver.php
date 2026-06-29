<?php
namespace App\Observers;

use App\Jobs\DeleteSurveyQuestionRelationsJob;
use App\Models\SurveyQuestion;

class SurveyQuestionObserver
{
    public function deleting(SurveyQuestion $surveyQuestion): void
    {
        DeleteSurveyQuestionRelationsJob::dispatchSync($surveyQuestion->id);
    }

}
