<?php
namespace App\Services\BackOffice;

use App\Http\Requests\LanguageRequest;
use App\Models\Language;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LanguageService
{
    public function new (): Language
    {
        return new Language();
    }

    public function find(string $slug): Language
    {
        return Language::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Language $language): Language
    {
        $language->load([
            'createdBy',

            'categories'   => fn($query)   => $query->latest()->limit(10),
            'categories.parent',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $language;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Language::query();

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
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
                'code',
            ], 'like', $likeSearch);
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(LanguageRequest $request, Language $language): array
    {
        $isNew       = empty($language->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $language, $isNew) {
                $language->name          = $request->input('name');
                $language->code          = $request->input('code');
                $language->brief         = $request->input('brief');
                $language->created_by_id = $isNew ? Auth::id() : $language->created_by_id;

                $language->save();
            });

            return [
                'status'  => 'success',
                'message' => __("status-messages.language.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {

            Log::error("Failed to {$statusEvent} language.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.language.save.failed'),
            ];
        }
    }

    public function delete(Language $language): array
    {
        try {

            DB::transaction(function () use ($language) {
                $language->forceDelete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.language.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Language delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.language.delete.failed'),
            ];
        }
    }

}
