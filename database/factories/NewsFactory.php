<?php
namespace Database\Factories;

use App\Helpers\NewsHelper;
use App\Helpers\SystemHelper;
use App\Helpers\UserHelper;
use App\Models\Category;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\News;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
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
        $language      = Language::where("code", SystemHelper::DEFAULT_LANGUAGE_CODE)->first() ?? null;
        $newsType      = Language::where("name", NewsHelper::NEWS_TYPE_STORY)->first() ?? null;

        $category = $this->getRandomCategory($language);
        $location = $this->getRandomLocation($language, $category);
        $event    = $this->getRandomEvent($language);

        $title           = $this->faker->name();
        $subTitle        = $this->faker->name();
        $contentShoulder = $this->faker->sentence(3);
        $brief           = $this->faker->sentence();
        $body            = $this->faker->sentence(100);

        $isPublished = $this->faker->boolean(50);

        return [
            'news_type_id'     => $newsType?->id ?? 1,
            "language_id"      => $language?->id ?? 1,
            "category_id"      => $category?->id ?? 1,

            "event_id"         => $event?->id ?? null,
            "location_id"      => $location?->id ?? null,

            'title'            => $title,
            'sub_title'        => $subTitle,
            'content_shoulder' => $contentShoulder,
            'brief'            => $brief,

            "body"             => $body,

            'seo_title'        => $title,
            'seo_brief'        => $brief,
            'seo_keywords'     => null,

            'source'           => null,

            "is_published"     => $isPublished,

            "created_by_id"    => $user?->id ?? 1,
        ];
    }

    private function getRandomCategory(Language $language): ?Category
    {
        return Category::where("language_id", $language->id)->inRandomOrder()->first() ?? null;
    }

    private function getRandomLocation(Language $language, ?Category $category): ?Location
    {
        if (! $category) {
            return null;
        }

        $allowed = match ($language->code) {
            SystemHelper::DEFAULT_LANGUAGE_CODE     => "National",
            SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE => "জাতীয়",
            default                                 => null,
        };

        if ($category->name !== $allowed) {
            return null;
        }

        return Location::where("language_id", $language->id)->where("category_id", $category->id)->inRandomOrder()->first() ?? null;
    }

    private function getRandomEvent(Language $language): ?Event
    {
        return Event::where("language_id", $language->id)->where("is_current", true)->inRandomOrder()->first() ?? null;
    }
}
