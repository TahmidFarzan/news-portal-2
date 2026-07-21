<?php
namespace App\Services\BackOffice;

use App\Models\Language;
use Exception;
use Illuminate\Http\Request;
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
        return Language::with([
            'createdBy',

            'categories'   => fn($query)   => $query->latest()->limit(10),
            'categories.parent',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('slug', $slug)->firstOrFail();
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
                "locale",
            ], 'like', $likeSearch);
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function setAsDefault(Language $language): array
    {
        try {

            $defaultLanguage = Language::where("is_default", true)->whereNot("id", $language->id)->first();

            DB::transaction(function () use ($language, $defaultLanguage) {
                if ($defaultLanguage) {
                    $defaultLanguage->is_default = false;
                    $defaultLanguage->save();
                }
                $language->is_default = true;
                $language->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.language.set_as_default.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Language set as default failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.language.set_as_default.failed'),
            ];
        }
    }

}
