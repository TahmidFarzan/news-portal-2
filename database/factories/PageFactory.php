<?php
namespace Database\Factories;

use App\Helpers\SystemHelper;
use App\Helpers\UserHelper;
use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
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
        $language      = Language::where("code", SystemHelper::LANGUAGE_DEFAULT_CODE)->first() ?? null;

        $title = $this->faker->name();
        $brief = $this->faker->sentence();

        return [
            'title'          => $title,
            'brief'          => $brief,
            'seo_title'      => $title,
            'seo_brief'      => $brief,
            "default_use_as" => null,
            "is_default"     => false,
            "is_published"   => false,
            "language_id"    => $language?->id ?? "1",
            "created_by_id"  => $user?->id ?? "1",
        ];
    }
}
