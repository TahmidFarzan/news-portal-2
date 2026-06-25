<?php
namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Models\Language;
use App\Models\Page;
use App\Models\User;
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
        $user     = User::where("is_super_admin", true)->inRandomOrder()->first();
        $language = Language::where("code", SeederHelper::LANGUAGE_EN_CODE)->first() ?? null;

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
