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

    public function firstById(string $id): Media
    {
        return Media::where('id', $id)->first();
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
                    'id'                => $media->id,
                    'name'              => $media->name,
                    'uuid'              => $media->uuid,
                    'mime_type'         => $media->mime_type,
                    'custom_properties' => $media->custom_properties,
                    'caption'           => $media->getCustomProperty('caption') ?? $media->model->name ?? "",
                    'alt'               => $media->getCustomProperty('alt') ?? $media->model->name ?? "",
                    'media_type'        => $media->getTypeFromMime(),
                    'original_url'      => $media->original_url,
                    'media_url'         => $media->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $media->getUrl(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $media->getUrl(),
                    'media_srcset'      => $media->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $media->getSrcset(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $media->getSrcset(),
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

    public static function copyOrUpdateMediaByMediaIds(array $mediaIds, $targetModel, string $mediaRole = MediaHelper::MEDIA_ROLE_DEFAULT): array
    {
        $replacementPairs = [];

        foreach ($mediaIds as $mediaId) {
            $replacementPair = self::copyOrUpdateMediaByMediaId((int) $mediaId, $targetModel, $mediaRole);

            if ($replacementPair === null) {
                continue;
            }

            $replacementPairs[] = $replacementPair;
        }

        return $replacementPairs;
    }

    public static function copyOrUpdateMediaByMediaId(int $mediaId, $targetModel, string $mediaRole = MediaHelper::MEDIA_ROLE_DEFAULT): ?object
    {
        DB::beginTransaction();
        try {
            $media = Media::with("model")->findOrFail($mediaId);

            $sourceModel = $media->model;

            $oldMediaId = $media->id;

            if (! $sourceModel || ! $targetModel || ! $media) {
                return null;
            }

            if ($sourceModel instanceof MediaUpload) {
                $media->model_id        = $targetModel->id;
                $media->model_type      = $targetModel->getMorphClass();
                $media->name            = $targetModel->name ?? $targetModel->title ?? $media->name;
                $media->collection_name = $targetModel->media_collection_name;
                $media->setCustomProperty('caption', $media->getCustomProperty('caption') ?? $product->name ?? null);
                $media->setCustomProperty('alt', $media->getCustomProperty('alt') ?? $product->name ?? null);
                $media->save();

                if ($sourceModel->getMedia($sourceModel->media_collection_name)->isEmpty()) {
                    $sourceModel->delete();
                }

                DB::commit();

                return (object) [
                    'old_media_id' => $oldMediaId,
                    'new_media_id' => $media->id,
                ];
            } else {
                $mediaExtension = pathinfo($media->original_url, PATHINFO_EXTENSION);
                $mediaFileName  = MediaHelper::generateMediaName(($targetModel->name ?? $targetModel->title ?? $media->name), $mediaExtension, 200);

                $newMedia = $targetModel->addMediaFromUrl($media->original_url)
                    ->usingName($targetModel->name ?? $targetModel->title ?? $media->name)
                    ->usingFileName($mediaFileName)
                    ->withCustomProperties(
                        [
                            'caption' => $media->getCustomProperty('caption') ?? $targetModel->name ?? $targetModel->title,
                            'alt'     => $media->getCustomProperty('alt') ?? $targetModel->name ?? $targetModel->title,
                            "role"    => $mediaRole,
                        ]
                    )
                    ->toMediaCollection($targetModel->media_collection_name);
                DB::commit();

                return (object) [
                    'old_media_id' => $oldMediaId,
                    'new_media_id' => $newMedia->id,
                ];
            }

        } catch (Exception $exception) {
            DB::rollback();

            $targetModelName = $targetModel->name ?? $targetModel->title ?? "";

            Log::error("Failed to transfer media {$targetModelName}.", [
                'old_media_id' => $mediaId,
                'exception'    => $exception,
            ]);
            return null;
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
