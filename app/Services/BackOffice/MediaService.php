<?php
namespace App\Services\BackOffice;

use App\Helpers\MediaHelper;
use App\Http\Requests\MediaQuickRequest;
use App\Models\MediaUpload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
        $media->media_url     = $media->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $media->getUrl(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $media->getUrl();
        $media->media_srcset  = $media->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $media->getSrcset(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $media->getSrcset();
        $media->activity_logs = $this->activityLogs($media);
        $media                = $media->toArray();
        return $media;
    }

    public function activityLogs(Media $media)
    {
        return Activity::with('causer')->where('subject_type', get_class($media))->where('subject_id', $media->id)->latest()->get();
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

    public function quickSave(MediaQuickRequest $request): array
    {
        DB::beginTransaction();

        try {
            $mediaUpload = MediaUpload::create();

            $file = $request->file('media');

            if ($file) {

                $extension = $file->getClientOriginalExtension();
                $fileName  = MediaHelper::generateMediaName("Upload", $extension, 200);

                $media = $mediaUpload->addMedia($file)
                    ->usingFileName($fileName)
                    ->withCustomProperties([
                        'caption' => $request->input('caption'),
                        'alt'     => $request->input('alt'),
                    ])
                    ->toMediaCollection($mediaUpload->media_collection_name);

                DB::commit();
            }

            return [
                'status'  => 'success',
                'message' => __('status-messages.media.save.success'),
                'media'   => (object) [
                    'id'              => $media->id,
                    'name'            => $media->name,
                    'uuid'            => $media->uuid,
                    'media_mime_type' => $media->mime_type,
                    'caption'         => $media->getCustomProperty('caption') ?? $media->model->name ?? "",
                    'alt'             => $media->getCustomProperty('alt') ?? $media->model->name ?? "",
                    'media_type'      => $media->getTypeFromMime(),
                    'url'             => $media->getUrl(),
                    'original_url'    => $media->original_url,
                    'media_url'       => $media->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $media->getUrl(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $media->getUrl(),
                    'media_srcset'    => $media->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $media->getSrcset(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $media->getSrcset(),
                ],
            ];
        } catch (Exception $ex) {
            DB::RollBack();

            Log::error('Media save failed.', [
                'exception'    => $ex,
                'request_data' => $request->input(),
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.media.save.failed'),
            ];
        }
    }

    public static function transferSingleMediaByMediaIds(array $mediaIds, $targetModel): void
    {
        foreach ($mediaIds as $mediaId) {
            self::transferSingleMediaByMediaIds($mediaId, $targetModel);
        }
    }

    public static function transferSingleMediaByMediaId(int $mediaId, $targetModel): void
    {
        DB::beginTransaction();
        try {
            $media = Media::with("model")->findOrFail($mediaId);

            $sourceModel = $media->model;

            if (! $sourceModel || ! $targetModel || ! $media) {
                return;
            }

            if ($media && $targetModel) {
                $media->model_id        = $targetModel->id;
                $media->model_type      = $targetModel->getMorphClass();
                $media->name            = $targetModel->name ?? $targetModel->title ?? $media->name;
                $media->collection_name = $targetModel->media_collection_name;
                $media->setCustomProperty('caption', $media->getCustomProperty('caption') ?? $product->name ?? null);
                $media->setCustomProperty('alt', $media->getCustomProperty('alt') ?? $product->name ?? null);
                $media->save();

                if ($sourceModel instanceof MediaUpload && $sourceModel->getMedia($sourceModel->media_collection_name)->isEmpty()) {
                    $sourceModel->delete();
                }

                DB::commit();
            }
        } catch (Exception $exception) {
            DB::rollback();

            $targeModelName = $targetModel->name ?? $targetModel->title ?? "";

            Log::error("Failed to transfer media {$targeModelName}.", [
                'exception' => $exception,
            ]);
        }
    }

    public function delete(Media $media): array
    {
        DB::beginTransaction();

        try {
            // if (Storage::exists($media->getUrl())) {
            //     Storage::delete($media->getUrl());
            // }
            // if ($media->responsive_images) {
            //     foreach ($media->responsive_images as $responsiveImage) {
            //         if (Storage::exists($responsiveImage->getUrl())) {
            //             Storage::delete($responsiveImage->getUrl());
            //         }
            //     }
            // }

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
