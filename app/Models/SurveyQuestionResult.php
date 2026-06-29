<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('survey_question_results')]
#[Fillable([
        'survey_question_id',
        'yes', 'no', 'no_comment',
        "participate_count",
    ])]

class SurveyQuestionResult extends Model
{
    use HasFactory;

    protected $appends = [
        'participate_count',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getParticipateCountAttribute(): int
    {
        return ( ($this->yes ?? 0) + ($this->no ?? 0) + ($this->no_comment ?? 0)) ?? 0;
    }

    public function surveyQuestion(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class);
    }
}
