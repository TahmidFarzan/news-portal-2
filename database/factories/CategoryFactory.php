<?php
namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Models\Category;
use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
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

        $name  = $this->faker->name();
        $brief = $this->faker->sentence();

        return [
            'name'          => $name,
            'brief'         => $brief,

            'name_tree'     => $name,
            'slug_tree'     => Str::slug($name),

            'seo_title'     => $name,
            'seo_brief'     => $brief,

            "language_id"   => $language?->id ?? "1",

            "parent_id"     => null,
            "created_by_id" => $user?->id ?? "1",
        ];
    }
}
