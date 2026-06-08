<?php
namespace App\Services\BackOffice;

use App\Helpers\TagifyHelper;
use App\Http\Requests\LocationRequest;
use App\Models\Location;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocationService
{
    public function getLocationTreeById(int $id): array
    {
        $location = Location::where('id', $id)->firstOrFail();

        return $location->bloodline()->pluck('id')->toArray();
    }

    public function new (): Location
    {
        return new Location();
    }

    public function find(string $slug): Location
    {
        return Location::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Location $location): Location
    {
        $location->load([
            'category',
            'category.parent',

            'parent',
            'bloodline',

            'language',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $location;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Location::query()->with("parent");

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('category_id')) {
            $query->whereIn('category_id', $request->input('category_id'));
        }

        if ($request->filled('parent_id')) {
            $query->whereIn('id', $this->getLocationTreeById((int) $request->input('parent_id')));
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

    public function save(LocationRequest $request, Location $location): array
    {
        $isNew       = empty($location->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $location, $isNew) {
                $seoKeywords = null;

                if ($request->input('seo_keywords')) {
                    $seoKeywords = TagifyHelper::dataStringFormatFull($request->input('seo_keywords'));
                }

                $location->name        = $request->input('name');
                $location->brief       = $request->input('brief');
                $location->parent_id   = $request->boolean('has_parent') ? $request->input('parent_id') : null;
                $location->category_id = $request->input('category_id');
                $location->language_id = $request->input('language_id');

                $boundaryGeojson = $request->input('boundary_geojson') ?? null;

                if (is_string($boundaryGeojson) && filled($boundaryGeojson)) {
                    $boundaryGeojson = json_decode($boundaryGeojson, true);
                }

                $location->boundary_geojson = $boundaryGeojson;
                $location->boundary_north   = $request->input('boundary_north');
                $location->boundary_south   = $request->input('boundary_south');
                $location->boundary_east    = $request->input('boundary_east');
                $location->boundary_west    = $request->input('boundary_west');

                $location->seo_title     = $request->input('seo_title', $request->input('name'));
                $location->seo_brief     = $request->input('seo_brief', $request->input('brief'));
                $location->seo_keywords  = $seoKeywords;
                $location->created_by_id = $isNew ? Auth::id() : $location->created_by_id;

                $location->save();
            });

            return [
                'status'  => 'success',
                'message' => __("status-messages.location.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {

            Log::error("Failed to {$statusEvent} location.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.location.save.failed'),
            ];
        }
    }

    public function delete(Location $location): array
    {

        try {
            DB::transaction(function () use ($location) {
                $location->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.location.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Location delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.location.delete.failed'),
            ];
        }
    }

}
