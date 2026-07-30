<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class QuizHelper
{
    public const QUESTION_ANSWER_TYPE_SINGLE     = 'Single';
    public const QUESTION_ANSWER_TYPE_MULTIPLE     = 'Multiple';

    public static function questionAnswerTypes(): Collection
    {
        return SystemHelper::toOptions([
            self::QUESTION_ANSWER_TYPE_SINGLE,
            self::QUESTION_ANSWER_TYPE_MULTIPLE,
        ]);
    }
}
