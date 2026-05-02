<?php
namespace App\Services\BackOffice;

use App\Helpers\MediaHelper;
use App\Helpers\TagifyHelper;
use App\Http\Requests\ContributorRequest;
use App\Models\Contributor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContributorService
{
    public function new (): Contributor
    {
        return new Contributor();
    }

    public function find(string $slug): Contributor
    {
        return Contributor::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Contributor $contributor): Contributor
    {
        $contributor->load([
            'language',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $contributor;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Contributor::query();

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
                    ->orWhere('brief', 'like', "%{$search}%")
                    ->orWhere('seo_brief', 'like', '%' . $search . '%')
                    ->orWhere('seo_title', 'like', '%' . $search . '%');
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(ContributorRequest $request, Contributor $contributor): array
    {
        DB::beginTransaction();

        try {
            $isNew       = empty($contributor->id);
            $statusEvent = $isNew ? "save" : "update";

            $seoKeywords = null;

            if ($request->input('seo_keywords')) {
                $seoKeywords = TagifyHelper::dataStringFormatFull($request->input('seo_keywords'));
            }

            $contributor->name            = $request->input('name');
            $contributor->brief           = $request->input('brief');
            $contributor->profile_details = $request->input('profile_details');
            $contributor->language_id     = $request->input('language_id');
            $contributor->seo_title       = $request->input('seo_title', $request->input('name'));
            $contributor->seo_brief       = $request->input('seo_brief', $request->input('brief'));
            $contributor->seo_keywords    = $seoKeywords;
            $contributor->created_by_id   = $isNew ? Auth::id() : $contributor->created_by_id;

            self::saveProfileImage($request, $contributor);

            $contributor->save();

            DB::commit();

            return [
                'status'  => 'success',
                'message' => __("status-messages.contributor.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error("Failed to {$statusEvent} contributor.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.contributor.save.failed'),
            ];
        }
    }

    public function delete(Contributor $contributor): array
    {
        DB::beginTransaction();

        try {
            $contributor->delete();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.contributor.delete.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('Contributor delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.contributor.delete.failed'),
            ];
        }
    }

    private static function saveProfileImage(ContributorRequest $request, Contributor $contributor)
    {
        if (! $request->hasFile('profile_image')) {
            return;
        }

        $existing = $contributor->getProfileImageAttribute();
        if ($existing) {
            $existing->delete();
        }

        $uploaded = $request->file('profile_image');

        if ($uploaded) {
            $name = MediaHelper::generateMediaName(
                $contributor->name,
                $uploaded->getClientOriginalExtension(),
                200
            );

            $contributor->addMedia($uploaded)
                ->usingFileName($name)
                ->withCustomProperties([
                    'alt'     => $user->name ?? null,
                    'caption' => $user->name ?? null,
                    'role'    => MediaHelper::MEDIA_ROLE_PROFILE_IMAGE,
                ])
                ->toMediaCollection($contributor->media_collection_name);
        }
    }

}
