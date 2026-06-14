<?php
namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Helpers\UserHelper;
use App\Models\BreakingNews;
use App\Models\Language;
use App\Models\News;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BreakingNews>
 */
class BreakingNewsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adminUserRole = UserRole::where("name", UserHelper::USER_ROLE_ADMIN)->inRandomOrder()->first();
        $user          = User::inRandomOrder()->where("user_role_id", $adminUserRole->id)->first() ?? null;
        $language      = Language::where("code", SeederHelper::LANGUAGE_EN_CODE)->first() ?? null;
        $news          = News::where("language_id", $language->id)->inRandomOrder()->first() ?? null;

        $title       = $this->faker->name();
        $isPublished = $this->faker->boolean(50);

        return [
            'title'         => $news?->title ?? $title,
            "language_id"   => $language?->id ?? "1",

            "is_published"  => $isPublished,
            "news_id"       => $news?->id ?? null,
            "created_by_id" => $user?->id ?? "1",
        ];
    }
}
