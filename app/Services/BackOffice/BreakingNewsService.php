<?php
namespace App\Services\BackOffice;

use App\Http\Requests\BreakingNewsRequest;
use App\Models\BreakingNews;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BreakingNewsService
{

    public function new (): BreakingNews
    {
        return new BreakingNews();
    }

    public function find(string $slug): BreakingNews
    {
        return BreakingNews::with([
            'createdBy',

            'language',
            'news',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('slug', $slug)->firstOrFail();
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = BreakingNews::query()->with(["language"]);

        if ($request->filled('news_type_id')) {
            $query->whereRelation('news', 'news_type_id', $request->input('news_type_id'));
        }

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('category_id')) {
            $query->whereRelation('news', 'category_id', $request->input('category_id'));
        }

        if ($request->filled('event_id')) {
            $query->whereRelation('news', 'event_id', $request->input('event_id'));
        }

        if ($request->filled('location_id')) {
            $query->whereRelation('news', 'location_id', $request->input('location_id'));
        }

        if ($request->filled("tag_id")) {
            $tagId = $request->input('tag_id');
            $query->whereHas('news.tags', function ($relationQuery) use ($tagId) {
                $relationQuery->where('tags.id', $tagId);
            });
        }

        if ($request->filled("contributor_id")) {
            $contributorId = $request->input('contributor_id');
            $query->whereHas('news.contributors', function ($relationQuery) use ($contributorId) {
                $relationQuery->where('contributors.id', $contributorId);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('search')) {
            $search     = $request->input('search');
            $likeSearch = "%{$search}%";

            $query->where(function ($query) use ($likeSearch) {
                $query->whereAny([
                    'title',
                ], 'like', $likeSearch)
                    ->orWhereHas('news', function ($newsQuery) use ($likeSearch) {
                        $newsQuery->whereAny([
                            'title',
                            'sub_title',
                            'content_shoulder',
                            'brief',
                            'seo_brief',
                            'seo_title',
                            'source',
                        ], 'like', $likeSearch);
                    });
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(BreakingNewsRequest $request, BreakingNews $breakingNews): array
    {
        $isNew       = empty($breakingNews->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $breakingNews, $isNew) {

                $breakingNews->news_id     = $request->input('news_id');
                $breakingNews->language_id = $request->input('language_id');

                $breakingNews->title         = $request->input('title');
                $breakingNews->is_published  = $request->input('is_published') ? true : false;
                $breakingNews->created_by_id = $isNew ? Auth::id() : $breakingNews->created_by_id;

                $breakingNews->save();
            });

            return [
                'status'  => 'success',
                'message' => __("status-messages.breaking_news.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {

            Log::error("Failed to {$statusEvent} news.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.breaking_news.save.failed'),
            ];
        }
    }

    public function trash(BreakingNews $breakingNews): array
    {

        try {
            DB::transaction(function () use ($breakingNews) {
                $breakingNews->is_published = false;
                $breakingNews->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.breaking_news.trash.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Breaking news trash failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.breaking_news.trash.failed'),
            ];
        }
    }

    public function restore(BreakingNews $breakingNews): array
    {

        try {

            DB::transaction(function () use ($breakingNews) {
                $breakingNews->is_published = true;
                $breakingNews->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.breaking_news.restore.success'),
            ];
        } catch (Exception $exception) {

            Log::error('News restore failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.breaking_news.restore.failed'),
            ];
        }
    }

    public function delete(BreakingNews $breakingNews): array
    {

        try {
            DB::transaction(function () use ($breakingNews) {
                $breakingNews->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.breaking_news.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Breaking news delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.breaking_news.delete.failed'),
            ];
        }
    }

}
