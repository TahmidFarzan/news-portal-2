<?php
namespace Database\Factories;

use App\Helpers\NewsHelper;
use App\Helpers\UserHelper;
use App\Models\News;
use App\Models\NewsPlacement;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<NewsPlacement>
 */
class NewsPlacementFactory extends Factory
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

        $pages        = [NewsHelper::PAGE_HOME, NewsHelper::PAGE_CATEGORY];
        $pageSections = [NewsHelper::PAGE_SECTION_LEAD_NEWS];

        $page        = Arr::random($pages);
        $pageSection = Arr::random($pageSections);

        $skipPageSection = ($page == NewsHelper::PAGE_CATEGORY) && ($pageSection == NewsHelper::PAGE_SECTION_CATEGORY_NEWS);
        $skipCategory    = ($page == NewsHelper::PAGE_HOME) && ($pageSection == NewsHelper::PAGE_SECTION_LEAD_NEWS);


        $news = News::query()
            ->whereDoesntHave('newsPlacements', function ($newsPlacementQuery) use (
                $page,
                $pageSection,
                $skipPageSection,
                $skipCategory
            ) {
                $newsPlacementQuery
                    ->where('page', $page)

                    ->when(! $skipPageSection, function ($query) use ($pageSection) {
                        $query->where('page_section', $pageSection);
                    })

                    ->when(! $skipCategory, function ($query) {
                        $query->whereColumn(
                            'news_placements.category_id',
                            'newses.category_id'
                        );
                    });
            })
            ->inRandomOrder()
            ->first() ?? null;

        $lastPosition = NewsPlacement::query()
            ->where('page', $page)
            ->when(! $skipPageSection, function ($query) use ($pageSection) {
                $query->where('page_section', $pageSection);
            })
            ->when(! $skipCategory, function ($query) use ($news) {
                $query->where('category_id', $news?->category_id);
            })
            ->max('position');


        return [
            "news_id"       => $news?->id,
            "page"          => $page,
            "page_section"  => $skipPageSection ? null : $pageSection,
            "category_id"   => $skipCategory ? null : ($news?->category_id ?? null),
            "position"      => $lastPosition ? $lastPosition + 1 : 1,
            "created_by_id" => $user?->id ?? 1,
        ];
    }
}
