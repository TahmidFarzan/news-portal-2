<?php
namespace App\Jobs;

use App\Helpers\MediaHelper;
use App\Models\News;
use App\Services\BackOffice\MediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class NewsGalleryImagesSyncJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    public $fail_limit = 3;

    public News $news;
    public $galleryImageIds;

    public function __construct(News $news, $galleryImageIds)
    {
        $this->news             = $news;
        $this->$galleryImageIds = $galleryImageIds;
    }

    public function progressCooldown(): int
    {
        return 10;
    }

    public function uniqueId()
    {
        return "news-{$this->news->slug}-gallery-image-sync-jobs" . Str::uuid()->toString() . Str::random(15) . '-' . time();
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

        $galleryImageIds = explode(',', $this->galleryImageIds);
        $galleryImageIds = array_filter($galleryImageIds);

        if (! count($galleryImageIds)) {
            return;
        }

        $replacementPairs = $mediaService->copyOrUpdateMediaByMediaIds(
            $galleryImageIds,
            $news,
            MediaHelper::ROLE_NEWS_GALLERY_IMAGE
        );

        if (! $replacementPairs) {
            return;
        }
    }
}
