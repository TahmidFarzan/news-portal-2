<?php

namespace App\Helpers;


class SessionHelper
{
    public const KEY_QUIZ = 'quiz';
    public const KEY_SUBMIT = 'submit';

    public const KEY_SURVEY = 'survey';
    public const KEY_SURVEY_QUESTION = 'survey-question';

    public static function sessionKeyGenerateSubmitQuiz(string | int $sessionId): string
    {
        $quizKey = self::KEY_QUIZ;
        $quizSubmit = self::KEY_SUBMIT;

        return "{$sessionId}_{$quizKey}_{$quizSubmit}";
    }


    public static function sessionKeyGenerateSubmitSurveyQuiestion(string | int $sessionId,string|int $surveyId, string|int $surveyQuestionId): string
    {
        $surveyKey = self::KEY_SURVEY;
        $surveyQuestion = self::KEY_SURVEY_QUESTION;

        return "{$sessionId}_{$surveyKey}:{$surveyId}_{$surveyQuestion}:{$surveyQuestionId}";
    }
}
