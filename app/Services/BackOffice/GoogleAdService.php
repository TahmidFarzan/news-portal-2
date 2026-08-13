<?php

namespace App\Services\BackOffice;

use App\Helpers\GoogleAdHelper;
use App\Http\Requests\GoogleAdRequest;
use App\Models\GoogleAd;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleAdService
{
    public function new(): GoogleAd
    {
        return new GoogleAd();
    }

    public function find(string $slug): GoogleAd
    {
        return GoogleAd::with([
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

        $query = GoogleAd::query();

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

    public function save(GoogleAdRequest $request, GoogleAd $googleAd): array
    {
        $isNew = empty($googleAd->id);
        $statusEvent = $isNew ? 'save' : 'update';

        try {
            DB::transaction(function () use ($request, $googleAd, $isNew) {
                $type = $request->input('type');

                $googleAd->name = $request->input('name');
                $googleAd->ad_unit_code = $request->input('ad_unit_code');
                $googleAd->gpt_slot_id = $request->input('gpt_slot_id');
                $googleAd->ad_sizes = $request->input('ad_sizes');
                $googleAd->type = $type;
                $googleAd->page = $request->input('page');
                $googleAd->placement = $type === GoogleAdHelper::TYPE_POPUP ? null : $request->input('placement');

                $googleAd->created_by_id = $isNew
                    ? Auth::id()
                    : $googleAd->created_by_id;

                $googleAd->save();
            });

            return [
                'status' => 'success',
                'message' => __("status-messages.google-ad.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to {$statusEvent} google ad.", [
                'exception' => $exception,
            ]);

            return [
                'status' => 'error',
                'message' => __("status-messages.google-ad.{$statusEvent}.failed"),
            ];
        }
    }

    public function delete(GoogleAd $googleAd): array
    {

        try {

            DB::transaction(function () use ($googleAd) {
                $googleAd->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.google-ad.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Google Ad delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.google-ad.delete.failed'),
            ];
        }
    }
}
