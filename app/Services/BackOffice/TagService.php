<?php
namespace App\Services\BackOffice;

use App\Helpers\TagifyHelper;
use App\Http\Requests\TagRequest;
use App\Models\Tag;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TagService
{
    public function new (): Tag
    {
        return new Tag();
    }

    public function find(string $slug): Tag
    {
        return Tag::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Tag $tag): Tag
    {
        $tag->load([
            'trend',
            'language',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $tag;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Tag::query()->with("trend");

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
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
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('details', 'like', "%{$search}%")
                    ->orWhere('seo_brief', 'like', '%' . $search . '%')
                    ->orWhere('seo_title', 'like', '%' . $search . '%');
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(TagRequest $request, Tag $tag): array
    {
        DB::beginTransaction();

        try {
            $isNew = empty($tag->id);
            $statusEvent = $isNew ? "save" : "update";

            $seoKeywords = null;

            if ($request->input('seo_keywords')) {
                $seoKeywords = TagifyHelper::dataStringFormatFull($request->input('seo_keywords'));
            }

            $tag->name          = $request->input('name');
            $tag->details       = $request->input('details');
            $tag->language_id   = $request->input('language_id');
            $tag->seo_title     = $request->input('seo_title', $request->input('name'));
            $tag->seo_brief     = $request->input('seo_brief', $request->input('details'));
            $tag->seo_keywords  = $seoKeywords;
            $tag->created_by_id = $isNew ? Auth::id() : $tag->created_by_id;

            $tag->save();

            DB::commit();

            return [
                'status'  => 'success',
                'message' => __("status-messages.tag.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error("Failed to {$statusEvent} tag.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.tag.save.failed'),
            ];
        }
    }

    public function delete(Tag $tag): array
    {
        DB::beginTransaction();

        try {
            $tag->delete();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.tag.delete.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('Tag delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.tag.delete.failed'),
            ];
        }
    }

}
