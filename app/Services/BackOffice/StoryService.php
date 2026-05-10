<?php
namespace App\Services\BackOffice;

use App\Helpers\MediaHelper;
use App\Helpers\TagifyHelper;
use App\Http\Requests\StoryRequest;
use App\Jobs\StoryContributorSyncJob;
use App\Jobs\StoryTagSyncJob;
use App\Models\Story;
use App\Services\BackOffice\MediaService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoryService
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function new (): Story
    {
        return new Story();
    }

    public function find(string $slug): Story
    {
        return Story::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Story $story): Story
    {
        $story->load([
            'createdBy',

            'language',

            'category',

            'event',
            'location',

            'tags',
            'tags.trend',

            'contributors',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $story;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Story::query()->with(["language", "category", "event", "location"]);

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled("tag_id")) {
            $tagId = $request->input('tag_id');
            $query->whereHas('tags', function ($relationQuery) use ($tagId) {
                $relationQuery->where('id', $tagId);
            });
        }

        if ($request->filled("contributor_id")) {
            $contributorId = $request->input('contributor_id');
            $query->whereHas('contributors', function ($relationQuery) use ($contributorId) {
                $relationQuery->where('id', $contributorId);
            });
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
                    ->orWhere('seo_title', 'like', '%' . $search . '%')
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(StoryRequest $request, Story $story): array
    {
        DB::beginTransaction();

        try {
            $isNew       = empty($story->id);
            $statusEvent = $isNew ? "save" : "update";

            $story->language_id = $request->input('language_id');
            $story->category_id = $request->input('category_id');
            $story->event_id    = $request->input('event_id');
            $story->location_id = $request->input('location_id');

            $story->title     = $request->input('title');
            $story->sub_title = $request->input('sub_title');
            $story->content_shoulder = $request->input('content_shoulder');
            $story->brief = $request->input('brief');
            $story->body      = $request->input('body');

            $story->seo_title    = $request->input('seo_title') ?? $request->input('title');
            $story->seo_brief    = $request->input('seo_brief') ?? $request->input('brief');
            $story->seo_keywords = TagifyHelper::dataStringFormatFull($request->input('seo_keywords')) ?? null;

            $story->is_published = $request->input('is_published') ? true : false;

            $story->writer = $request->input('writer');
            $story->source = $request->input('source');

            $story->created_by_id = $isNew ? Auth::id() : $story->created_by_id;

            $story->save();

            DB::commit();

            self::featureImageSave($request, $story);
            self::featureImageMobileSave($request, $story);
            self::syncContentMedia($request, $story);

            self::syncAttributesJob($request, $story);

            return [
                'status'  => 'success',
                'message' => __("status-messages.story.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error("Failed to {$statusEvent} story.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.story.save.failed'),
            ];
        }
    }

    public function delete(Story $story): array
    {
        DB::beginTransaction();

        try {
            $story->is_published = false;
            $story->save();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.story.delete.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('Story delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.story.delete.failed'),
            ];
        }
    }

    public function restore(Story $story): array
    {
        DB::beginTransaction();

        try {
            $story->is_published = true;
            $story->save();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.story.restore.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('Story restore failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.story.restore.failed'),
            ];
        }
    }

    private function syncAttributesJob(StoryRequest $request, Story $story)
    {
        if ($request->input('tag_ids')) {
            StoryTagSyncJob::dispatch($story, $request->input('tag_ids'));
        }

        if ($request->input('contributor_ids')) {
            StoryContributorSyncJob::dispatch($story, $request->input('contributor_ids'));
        }
    }

    private function featureImageSave(StoryRequest $request, Story $story)
    {
        if ($request->hasFile('upload_feature_image')) {
            self::deleteExtingFeatureImage($story);

            $featureImage = $request->file('upload_feature_image');

            if ($featureImage) {
                $extension = $featureImage->getClientOriginalExtension();
                $fileName  = MediaHelper::generateMediaName($story->title, $extension, 200);

                $story->addMedia($featureImage)
                    ->usingFileName($fileName)
                    ->usingName($story->title)
                    ->withCustomProperties(
                        [
                            "alt"     => $story->title,
                            "caption" => $request->input('feature_image_caption'),
                            "role"    => MediaHelper::MEDIA_ROLE_STORY_FEATURE_IMAGE,
                        ]
                    )
                    ->toMediaCollection($story->media_collection_name);
            }
        }

        if ($request->input('selected_feature_image_url')) {
            self::deleteExtingFeatureImage($story);
            $mediaFeatureImageUrl       = $request->input('selected_feature_image_url');
            $mediaFeatureImageExtension = pathinfo($mediaFeatureImageUrl, PATHINFO_EXTENSION);
            $mediaFeatureImageFileName  = MediaHelper::generateMediaName($story->name, $mediaFeatureImageExtension, 200);

            $story->addMediaFromUrl($mediaFeatureImageUrl)
                ->usingName($story->title)
                ->usingFileName($mediaFeatureImageFileName)
                ->withCustomProperties(
                    [
                        'caption' => $request->input('feature_image_caption'),
                        'alt'     => $story->title,
                        "role"    => MediaHelper::MEDIA_ROLE_STORY_FEATURE_IMAGE,
                    ]
                )
                ->toMediaCollection($story->media_collection_name);
        }
    }

    private function featureImageMobileSave(StoryRequest $request, Story $story)
    {
        if ($request->hasFile('upload_feature_image_mobile')) {
            self::deleteExtingFeatureImageMobile($story);

            $featureImageMobile = $request->file('upload_feature_image_mobile');

            if ($featureImageMobile) {
                $extension = $featureImageMobile->getClientOriginalExtension();
                $fileName  = MediaHelper::generateMediaName($story->title, $extension, 200);

                $story->addMedia($featureImageMobile)
                    ->usingFileName($fileName)
                    ->usingName($story->title)
                    ->withCustomProperties(
                        [
                            "alt"     => $story->title,
                            "caption" => $story->input('feature_image_caption'),
                            "role"    => MediaHelper::MEDIA_ROLE_STORY_FEATURE_IMAGE_MOBILE,
                        ]
                    )
                    ->toMediaCollection($story->media_collection_name);
            }
        }

        if ($request->input('selected_feature_image_mobile_url')) {
            self::deleteExtingFeatureImageMobile($story);
            $mediaFeatureImageMobileUrl       = $request->input('selected_feature_image_mobile_url');
            $mediaFeatureImageMobileExtension = pathinfo($mediaFeatureImageMobileUrl, PATHINFO_EXTENSION);
            $mediaFeatureImageMobileFileName  = MediaHelper::generateMediaName($story->name, $mediaFeatureImageMobileExtension, 200);

            $story->addMediaFromUrl($mediaFeatureImageMobileUrl)
                ->usingName($story->title)
                ->usingFileName($mediaFeatureImageMobileFileName)
                ->withCustomProperties(
                    [
                        'caption' => $story->input('upload_feature_image_mobile_caption'),
                        'alt'     => $story->title,
                        "role"    => MediaHelper::MEDIA_ROLE_STORY_FEATURE_IMAGE_MOBILE,
                    ]
                )
                ->toMediaCollection($story->media_collection_name);
        }
    }

    private function syncContentMedia(StoryRequest $request, Story $story): void
    {
        if (! $request->filled('editor_media_ids')) {
            return;
        }

        $contentMediaIds = explode(',', $request->input('editor_media_ids'));
        $contentMediaIds = array_filter($contentMediaIds);

        if (! count($contentMediaIds)) {
            return;
        }

        $replacementPairs = $this->mediaService->copyOrUpdateMediaByMediaIds(
            $contentMediaIds,
            $story,
            MediaHelper::MEDIA_ROLE_STORY_CONTENT_IMAGE
        );

        if (! $replacementPairs) {
            return;
        }

        $body = $story->body ?? '';

        foreach ($replacementPairs as $replacementPair) {
            if ($replacementPair->old_media_id == $replacementPair->new_media_id) {
                continue;
            }

            $oldMedia = $this->mediaService->firstById($replacementPair->old_media_id);
            $newMedia = $this->mediaService->firstById($replacementPair->new_media_id);

            if (! $oldMedia || ! $newMedia) {
                continue;
            }

            $replaceableUrls = [
                [
                    'old' => $oldMedia->url ?? null,
                    'new' => $newMedia->url ?? null,
                ],
                [
                    'old' => $oldMedia->original_url ?? null,
                    'new' => $newMedia->original_url ?? null,
                ],
                [
                    'old' => $oldMedia->media_url ?? null,
                    'new' => $newMedia->media_url ?? null,
                ],
                [
                    'old' => $oldMedia->media_srcset ?? null,
                    'new' => $newMedia->media_srcset ?? null,
                ],
            ];

            foreach ($replaceableUrls as $replaceableUrl) {
                if (! $replaceableUrl['old'] || ! $replaceableUrl['new']) {
                    continue;
                }

                $body = str_replace(
                    $replaceableUrl['old'],
                    $replaceableUrl['new'],
                    $body
                );
            }
        }

        if ($body !== $story->body) {
            $story->body = $body;
            $story->save();
        }
    }

    private static function deleteExtingFeatureImage(Story $story)
    {
        $extingFeatureImage = $story->feature_image;
        if ($extingFeatureImage) {
            $extingFeatureImage->delete();
        }
    }

    private static function deleteExtingFeatureImageMobile(Story $story)
    {
        $extingFeatureImageMobile = $story->feature_image_mobile;
        if ($extingFeatureImageMobile) {
            $extingFeatureImageMobile->delete();
        }
    }
}
