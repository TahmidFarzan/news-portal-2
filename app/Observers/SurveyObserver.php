<?php
namespace App\Observers;

use App\Jobs\DeleteSurveyRelationsJob;
use App\Models\Survey;

class SurveyObserver
{
    public function deleting(Survey $survey): void
    {
        DeleteSurveyRelationsJob::dispatchSync($survey->id);
    }

}
