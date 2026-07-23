<?php
namespace App\Services\BackOffice;

use App\Helpers\TagifyHelper;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    public function getCategoryTreeById(int $id): array
    {
        $category = Category::where('id', $id)->firstOrFail();

        return $category->bloodline()->pluck('id')->toArray();
    }

    public function new (): Category
    {
        return new Category();
    }

    public function find(string $slug): Category
    {
        return Category::with([
            'locations',

            'parent',
            'bloodline',

            'language',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('slug', $slug)->firstOrFail();
    }

    public function firstByIdOrSlug(int | string $slugOrId): Category | null
    {
        return Category::with([
            'children',
            'locations',

            'parent',
            'bloodline',

            'language',

            'createdBy',
        ])->where("id", $slugOrId)->orWhere("slug", $slugOrId)->first() ?? null;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Category::query()->with("parent");

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('parent_id')) {
            $query->whereIn('id', $this->getCategoryTreeById((int) $request->input('parent_id')));
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
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
                'name',
                'brief',
                'seo_brief',
                'seo_title',
            ], 'like', $likeSearch);
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(CategoryRequest $request, Category $category): array
    {
        $isNew       = empty($category->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $category, $isNew) {
                $seoKeywords = null;

                if ($request->input('seo_keywords')) {
                    $seoKeywords = TagifyHelper::dataStringFormatFull($request->input('seo_keywords'));
                }

                $category->name          = $request->input('name');
                $category->brief         = $request->input('brief');
                $category->language_id   = $request->input('language_id');
                $category->parent_id     = $request->boolean('has_parent') ? $request->input('parent_id') : null;
                $category->seo_title     = $request->input('seo_title', $request->input('name'));
                $category->seo_brief     = $request->input('seo_brief', $request->input('brief'));
                $category->seo_keywords  = $seoKeywords;
                $category->created_by_id = $isNew ? Auth::id() : $category->created_by_id;

                $category->save();
            });
            return [
                'status'  => 'success',
                'message' => __("status-messages.category.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {

            Log::error("Failed to {$statusEvent} category.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.category.save.failed'),
            ];
        }
    }

    public function delete(Category $category): array
    {
        try {
            DB::transaction(function () use ($category) {
                $category->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.category.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Category delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.category.delete.failed'),
            ];
        }
    }

}
