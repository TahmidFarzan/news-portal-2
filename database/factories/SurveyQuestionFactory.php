<?php
namespace Database\Factories;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyQuestion>
 */
class SurveyQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user     = User::where("is_super_admin", true)->inRandomOrder()->first();
        $survey   = Survey::where("is_active", true)->inRandomOrder()->first();

        $question = $this->faker->sentence();

        return [
            'question'      => $question,
            "survey_id"     => $survey?->id ?? "1",
            "created_by_id" => $user?->id ?? "1",
        ];
    }
}
