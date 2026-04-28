<?php
namespace App\Services\BackOffice;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Activitylog\Models\Activity;
use App\Helpers\MediaHelper;

class MediaService
{
    public function new (): Media
    {
        return new Media();
    }

    public function find(string $slug): Media
    {
        return Media::where('slug', $slug)->orWhere('uuid', $slug)->firstOrFail();
    }

    public function loadRelations(Media $media)
    {
        $media->load([
            'model',
        ]);
        $media->media_url = $media->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ?  $media->getUrl(MediaHelper::DEFAULT_MEDIA_CONVERSION) :  $media->getUrl();
        $media->media_srcset = $media->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ?  $media->getSrcset(MediaHelper::DEFAULT_MEDIA_CONVERSION) :  $media->getSrcset();
        $media->activity_logs = $this->activityLogs($media);
        $media = $media->toArray();
        return $media;
    }

    public function activityLogs(Media $media)
    {
        return Activity::with('causer')->where('subject_type',  get_class($media))->where('subject_id', $media->id)->latest()->get();
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Media::query()->with("model");

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function delete(Media $media): array
    {
        DB::beginTransaction();

        try {
            if (Storage::exists($media->getUrl())) {
                Storage::delete($media->getUrl());
            }
            if ($media->responsive_images) {
                foreach ($media->responsive_images as $responsiveImage) {
                    if (Storage::exists($responsiveImage->getUrl())) {
                        Storage::delete($responsiveImage->getUrl());
                    }
                }
            }

            $media->delete();

            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.media.delete.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('Media delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.media.delete.failed'),
            ];
        }
    }

}
