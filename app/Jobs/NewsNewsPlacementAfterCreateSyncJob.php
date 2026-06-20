<?php
namespace App\Jobs;

use App\Helpers\PageHelper;
use App\Models\News;
use App\Models\NewsPlacement;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class NewsNewsPlacementAfterCreateSyncJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public $fail_limit = 3;

    public News $news;

    public function __construct(News $news, )
    {
        $this->news = $news;
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        return "news-{$this->news->slug}-news-placement-after-create-sync-jobs" . Str::uuid()->toString() . Str::random(15) . '-' . time();
    }

    public function retryAfter()
    {
        return 60;
    }

    public function backoff()
    {
        return [61, 123, 185];
    }

    public function handle(): void
    {
        $news = $this->news;

        $homePage     = PageHelper::PAGE_HOME;
        $categoryPage = PageHelper::PAGE_CATEGORY;

        $leadNewsSection     = PageHelper::PAGE_SECTION_LEAD_NEWS;
        $categoryNewsSection = PageHelper::PAGE_SECTION_CATEGORY_NEWS;

        try {

            DB::transaction(function () use ($news, $homePage, $categoryPage, $leadNewsSection, $categoryNewsSection) {

                $homeLeadNewsPositionExit = NewsPlacement::query()
                    ->where('news_id', $news->id)
                    ->where('page', $homePage)
                    ->where('page_section', $leadNewsSection)->exists();

                $homeCategoryNewsPositionExit = NewsPlacement::query()
                    ->where('news_id', $news->id)
                    ->where('page', $homePage)
                    ->where('page_section', $categoryNewsSection)
                    ->when($news->category_id !== null, function ($query) use ($news) {
                        $query->where('category_id', $news->category_id);
                    })->exists();

                $categoryLeadNewsPositionExit = NewsPlacement::query()
                    ->where('news_id', $news->id)
                    ->where('page', $categoryPage)
                    ->where('page_section', $leadNewsSection)
                    ->when($news->category_id !== null, function ($query) use ($news) {
                        $query->where('category_id', $news->category_id);
                    })->exists();

                if (! $homeLeadNewsPositionExit) {
                    NewsPlacement::create([
                        'news_id'       => $news->id,
                        'page'          => $homePage,
                        'page_section'  => $leadNewsSection,
                        'category_id'   => null,
                        'position'      => 10,
                        'created_by_id' => $news->create_by_id,
                    ]);
                }

                if (! $homeCategoryNewsPositionExit) {
                    NewsPlacement::create([
                        'news_id'       => $news->id,
                        'page'          => $homePage,
                        'page_section'  => $categoryNewsSection,
                        'category_id'   => $news->category_id,
                        'position'      => 10,
                        'created_by_id' => $news->create_by_id,
                    ]);
                }

                if (! $categoryLeadNewsPositionExit) {
                    NewsPlacement::create([
                        'news_id'       => $news->id,
                        'page'          => $categoryPage,
                        'page_section'  => $leadNewsSection,
                        'category_id'   => $news->category_id,
                        'position'      => 10,
                        'created_by_id' => $news->create_by_id,
                    ]);
                }
            });

        } catch (Exception $exception) {

            Log::error('News news placement after create sync fail.', [
                'exception' => $exception,
            ]);
        }
    }
}
