<?php
namespace App\Services\BackOffice;

use App\Helpers\MediaHelper;
use App\Helpers\TagifyHelper;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventService
{
    public function new (): Event
    {
        return new Event();
    }

    public function find(string $slug): Event
    {
        return Event::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Event $event): Event
    {
        $event->load([
            'language',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $event;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Event::query();

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

    public function save(EventRequest $request, Event $event): array
    {
        $isNew       = empty($event->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $event, $isNew) {
                $seoKeywords = null;

                if ($request->input('seo_keywords')) {
                    $seoKeywords = TagifyHelper::dataStringFormatFull($request->input('seo_keywords'));
                }

                $event->name        = $request->input('name');
                $event->brief       = $request->input('brief');
                $event->language_id = $request->input('language_id');

                $event->seo_title     = $request->input('seo_title', $request->input('name'));
                $event->seo_brief     = $request->input('seo_brief', $request->input('brief'));
                $event->seo_keywords  = $seoKeywords;
                $event->created_by_id = $isNew ? Auth::id() : $event->created_by_id;

                $event->save();

                self::saveDesktopBannerImage($request, $event);
                self::saveMobileBannerImage($request, $event);
            });

            return [
                'status'  => 'success',
                'message' => __("status-messages.event.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {

            Log::error("Failed to {$statusEvent} event.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.event.save.failed'),
            ];
        }
    }

    public function delete(Event $event): array
    {

        try {

            DB::transaction(function () use ($event) {
                $event->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.event.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Event delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.event.delete.failed'),
            ];
        }
    }

    private static function saveDesktopBannerImage(EventRequest $request, Event $event)
    {
        if (! $request->hasFile('desktop_banner_image')) {
            return;
        }

        $existing = $event->getDesktopBannerImageAttribute();
        if ($existing) {
            $existing->delete();
        }

        $uploaded = $request->file('desktop_banner_image');

        if ($uploaded) {
            $name = MediaHelper::generateMediaName(
                $event->name,
                $uploaded->getClientOriginalExtension(),
                200
            );

            $event->addMedia($uploaded)
                ->usingFileName($name)
                ->withCustomProperties([
                    'alt'     => $user->name ?? null,
                    'caption' => $user->name ?? null,
                    'role'    => MediaHelper::ROLE_EVENT_BANNER_IMAGE_DESKTOP,
                ])
                ->toMediaCollection($event->media_collection_name);
        }
    }

    private static function saveMobileBannerImage(EventRequest $request, Event $event)
    {
        if (! $request->hasFile('mobile_banner_image')) {
            return;
        }

        $existing = $event->getMobileBannerImageAttribute();
        if ($existing) {
            $existing->delete();
        }

        $uploaded = $request->file('mobile_banner_image');

        if ($uploaded) {
            $name = MediaHelper::generateMediaName(
                $event->name,
                $uploaded->getClientOriginalExtension(),
                200
            );

            $event->addMedia($uploaded)
                ->usingFileName($name)
                ->withCustomProperties([
                    'alt'     => $user->name ?? null,
                    'caption' => $user->name ?? null,
                    'role'    => MediaHelper::ROLE_EVENT_BANNER_IMAGE_MOBILE,
                ])
                ->toMediaCollection($event->media_collection_name);
        }
    }

}
