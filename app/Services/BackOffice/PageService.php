<?php
namespace App\Services\BackOffice;

use App\Helpers\TagifyHelper;
use App\Http\Requests\PageRequest;
use App\Models\Page;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PageService
{
    public function getPageTreeById(int $id): array
    {
        $page = Page::where('id', $id)->firstOrFail();

        return $page->bloodline()->pluck('id')->toArray();
    }
    public function new (): Page
    {
        return new Page();
    }

    public function find(string $slug): Page
    {
        return Page::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Page $page): Page
    {
        $page->load([
            'language',

            'parent',
            'bloodline',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $page;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Page::query()->with("language");

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('parent_id')) {
            $query->whereIn('id', $this->getPageTreeById((int) $request->input('parent_id')));
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('search')) {
            $search     = $request->input('search');
            $likeSearch = "%{$search}%";

            $query->whereAny([
                'title',
                'brief',
                'seo_brief',
                'seo_title',
            ], 'like', $likeSearch);
        }
        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(PageRequest $request, Page $page): array
    {
        $isNew       = empty($page->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $page, $isNew) {
                $seoKeywords = null;

                if ($request->input('seo_keywords')) {
                    $seoKeywords = TagifyHelper::dataStringFormatFull($request->input('seo_keywords'));
                }

                $page->title       = $request->input('title');
                $page->brief       = $request->input('brief');
                $page->language_id = $request->input('language_id');
                $page->parent_id   = $request->input('parent_id');

                $page->body = $page->is_default ? null : $request->input('body');

                $page->seo_title    = $request->input('seo_title', $request->input('title'));
                $page->seo_brief    = $request->input('seo_brief', $request->input('brief'));
                $page->seo_keywords = $seoKeywords;

                $page->created_by_id = $isNew ? Auth::id() : $page->created_by_id;

                if ($isNew) {
                    $page->is_published = $request->input('is_published') ? true : false;
                } else {
                    $page->is_published = $page->is_default ? $page->is_published : ($request->input('is_published') ? true : false);
                }

                $page->save();
            });

            return [
                'status'  => 'success',
                'message' => __("status-messages.page.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to {$statusEvent} page.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.page.save.failed'),
            ];
        }
    }

    public function trash(Page $page): array
    {
        if ($page->is_default) {
            return [
                'status'  => 'warning',
                'message' => __('status-messages.page.update.default_warning'),
            ];
        }

        try {
            DB::transaction(function () use ($page) {
                $page->is_published = false;
                $page->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.page.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Page delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.page.delete.failed'),
            ];
        }
    }

    public function restore(Page $page): array
    {

        if ($page->is_default) {
            return [
                'status'  => 'warning',
                'message' => __('status-messages.page.update.default_warning'),
            ];
        }

        try {

            DB::transaction(function () use ($page) {
                $page->is_published = true;
                $page->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.page.restore.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Page restore failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.page.restore.failed'),
            ];
        }
    }

    public function delete(Page $page): array
    {

        try {

            DB::transaction(function () use ($page) {
                $page->forcedelete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.page.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Page delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.page.delete.failed'),
            ];
        }
    }

}
