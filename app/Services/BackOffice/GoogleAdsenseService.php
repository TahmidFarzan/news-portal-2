<?php

namespace App\Services\BackOffice;

use App\Helpers\ThemeHelper;
use App\Http\Requests\GoogleAdsenseRequest;
use App\Models\GoogleAdsense;
use App\Services\BackOffice\ThemeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleAdsenseService
{
    public ThemeService $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function new(): GoogleAdsense
    {
        return new GoogleAdsense();
    }

    public function find(string $slug): GoogleAdsense
    {
        return GoogleAdsense::with([
            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('slug', $slug)->firstOrFail();
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = GoogleAdsense::query();

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('position')) {
            $query->where('position', $request->input('position'));
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
                'slot_id',
            ], 'like', $likeSearch);
        }
        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(GoogleAdsenseRequest $request, GoogleAdsense $googleAdsense): array
    {
        $isNew       = empty($googleAdsense->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $googleAdsense, $isNew) {
                $googleAd = $this->themeService->findByName(ThemeHelper::NAME_GOOGLE_AD);

                $googleAdsenseClientId = data_get(
                    $googleAd?->options,
                    ThemeHelper::OPTION_GOOGLE_ADSENSE_CLIENT_ID . '.value',
                    null
                );
                $googleAdsense->name                      = $request->input('name');
                $googleAdsense->slot_id                   = $request->input('slot_id');
                $googleAdsense->client_id                 = $googleAdsenseClientId;
                $googleAdsense->type                      = $request->input('type');
                $googleAdsense->position                  = $request->input('position');
                $googleAdsense->use_full_width_responsive = $request->boolean('use_full_width_responsive');

                $googleAdsense->created_by_id = $isNew ? Auth::id() : $googleAdsense->created_by_id;

                $googleAdsense->save();
            });
            return [
                'status'  => 'success',
                'message' => __("status-messages.google-adsense.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to {$statusEvent} google adsense.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.google-adsense.save.failed'),
            ];
        }
    }

    public function delete(GoogleAdsense $googleAdsense): array
    {

        try {

            DB::transaction(function () use ($googleAdsense) {
                $googleAdsense->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.google-adsense.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Google adsense delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.google-adsense.delete.failed'),
            ];
        }
    }
}
