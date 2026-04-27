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

            'categories' => fn($query) => $query->latest()->limit(10),
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
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(LanguageRequest $request, Language $language): array
    {
        DB::beginTransaction();

        try {
            $isNew = empty($language->id);
            $statusEvent = $isNew ?  "save": "update";

            $language->name          = $request->input('name');
            $language->code          = $request->input('code');
            $language->details       = $request->input('details');
            $language->created_by_id = $isNew ? Auth::id() : $language->created_by_id;

            $language->save();

            DB::commit();

            return [
                'status'  => 'success',
                'message' => __("status-messages.language.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            DB::rollback();


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
        DB::beginTransaction();

        try {
            $language->forceDelete();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.language.delete.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

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
