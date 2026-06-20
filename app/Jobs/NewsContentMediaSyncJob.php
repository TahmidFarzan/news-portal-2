<?php
namespace App\Jobs;

use App\Helpers\MediaHelper;
use App\Models\News;
use App\Services\BackOffice\MediaService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class NewsContentMediaSyncJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public $fail_limit = 3;

    public News $news;
    public $editorMediaIds;


    public function __construct(News $news, $editorMediaIds)
    {
        $this->news            = $news;
        $this->$editorMediaIds = $editorMediaIds;
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        return "news-{$this->news->slug}-content-media-sync-jobs" . Str::uuid()->toString() . Str::random(15) . '-' . time();
    }

    public function retryAfter()
    {
        return 60;
    }

    public function backoff()
    {
        return [61, 123, 185];
    }

    public function handle(MediaService $mediaService): void
    {
        $news = $this->news;

        $editorMediaIdIds = explode(',', $this->editorMediaIds);
        $editorMediaIdIds = array_filter($editorMediaIdIds);

        if (! count($editorMediaIdIds)) {
            return;
        }

        $replacementPairs = $mediaService->copyOrUpdateMediaByMediaIds(
            $editorMediaIdIds,
            $news,
            MediaHelper::ROLE_NEWS_CONTENT_IMAGE
        );

        if (! $replacementPairs) {
            return;
        }

        $body = $news->body ?? '';

        foreach ($replacementPairs as $replacementPair) {
            if ($replacementPair->old_media_id == $replacementPair->new_media_id) {
                continue;
            }

            $oldMedia = $mediaService->firstById($replacementPair->old_media_id);
            $newMedia = $mediaService->firstById($replacementPair->new_media_id);

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

        try {

            if ($body !== $news->body) {
                $news->body = $body;
                $news->save();

                DB::transaction(function () use ($body, $news) {

                    $news->body = $body;
                    $news->save();
                });
            }
        } catch (Exception $exception) {

            Log::error('News content media sync fail.', [
                'exception' => $exception,
            ]);
        }
    }
}
