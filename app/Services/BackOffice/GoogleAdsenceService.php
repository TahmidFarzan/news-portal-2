<?php
namespace App\Services\BackOffice;

use App\Http\Requests\GoogleAdsenceRequest;
use App\Models\GoogleAdsence;
use Exception;
use App\Helpers\ThemeHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\BackOffice\ThemeService;

class GoogleAdsenceService
{
    public ThemeService $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function new (): GoogleAdsence
    {
        return new GoogleAdsence();
    }

    public function find(string $slug): GoogleAdsence
    {
        return GoogleAdsence::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(GoogleAdsence $googleAdsence): GoogleAdsence
    {
        $googleAdsence->load([

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $googleAdsence;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = GoogleAdsence::query();

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

    public function save(GoogleAdsenceRequest $request, GoogleAdsence $googleAdsence): array
    {
        $isNew       = empty($googleAdsence->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $googleAdsence, $isNew) {
                $googleAdsenceClient = $this->themeService->findByGroupAndLabel(ThemeHelper::GROUP_APP,ThemeHelper::OPTION_GOOGLE_ADSENCE_CLIENT_ID);

                $googleAdsence->name                      = $request->input('name');
                $googleAdsence->slot_id                   = $request->input('slot_id');
                $googleAdsence->client_id                 = $googleAdsenceClient->value ?? config("util.google-ad.test_client_id");
                $googleAdsence->type                      = $request->input('type');
                $googleAdsence->position                  = $request->input('position');
                $googleAdsence->use_full_width_responsive = $request->boolean('use_full_width_responsive');

                $googleAdsence->created_by_id = $isNew ? Auth::id() : $googleAdsence->created_by_id;

                $googleAdsence->save();
            });
            return [
                'status'  => 'success',
                'message' => __("status-messages.google-adsence.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to {$statusEvent} google adsence.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.google-adsence.save.failed'),
            ];
        }
    }

    public function delete(GoogleAdsence $googleAdsence): array
    {

        try {

            DB::transaction(function () use ($googleAdsence) {
                $googleAdsence->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.google-adsence.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Google adsence delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.google-adsence.delete.failed'),
            ];
        }
    }

}
