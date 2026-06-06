<?php
namespace Database\Seeders;

use App\Helpers\MediaHelper;
use App\Helpers\NewsHelper;
use App\Models\News;
use App\Models\NewsType;
use Illuminate\Database\Seeder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class NewsMediaSeeder extends Seeder
{
    public function run(): void
    {
        Media::query()
            ->where('model_type', News::class)
            ->where('collection_name', 'News')
            ->chunkById(500, function ($mediaItems) {
                foreach ($mediaItems as $media) {
                    $media->delete();
                }
            });

        News::query()
            ->select(['id',
                'language_id',
                'news_type_id',
                'title',
                'slug'
            ])
            ->with(['newsType'])
            ->orderBy("id","desc")
            ->chunkById(1000, function ($news) {

                foreach ($news as $perNews) {
                    $this->addFeatureImage($perNews);
                    $this->addFeatureImageMobile($perNews);
                    if ($perNews->newsType?->name == NewsHelper::NEWS_TYPE_IMAGE_GALLERY) {
                        for ($i = 0; $i < 5; $i++) {
                            $this->addGalleryImage($perNews, $i);
                        }
                    }
                }
            });
    }

    private function addGalleryImage(News $news, int | string $imageSequence): void
    {
        $imageUrl = asset("uploads/images/news/news-gallery-image-3_2.png");

        if (! (($imageSequence % 2) == 0)) {
            $imageUrl = asset("uploads/images/news/news-gallery-image-2_3.png");
        }

        $imageExtension = pathinfo($imageUrl, PATHINFO_EXTENSION);
        $imageFileName  = MediaHelper::generateMediaName($news->title, $imageExtension, 200);
        $news->addMediaFromUrl($imageUrl)
            ->usingName($news->title)
            ->usingFileName($imageFileName)
            ->withCustomProperties(
                [
                    'caption' => $news->title,
                    'alt'     => $news->title,
                    "role"    => MediaHelper::ROLE_NEWS_GALLERY_IMAGE,
                ]
            )
            ->toMediaCollection($news->media_collection_name);
    }

    private function addFeatureImage(News $news): void
    {
        $imageUrl = asset("uploads/images/news/story-feature-image.png");

        if ($news->newsType?->name == NewsHelper::NEWS_TYPE_VIDEO) {
            $imageUrl = asset("uploads/images/news/video-feature-image.png");
        }

        if ($news->newsType?->name == NewsHelper::NEWS_TYPE_IMAGE_GALLERY) {
            $imageUrl = asset("uploads/images/news/image-gallery-feature-image.png");
        }

        $imageExtension = pathinfo($imageUrl, PATHINFO_EXTENSION);
        $imageFileName  = MediaHelper::generateMediaName($news->title, $imageExtension, 200);
        $news->addMediaFromUrl($imageUrl)
            ->usingName($news->title)
            ->usingFileName($imageFileName)
            ->withCustomProperties(
                [
                    'caption' => $news->title,
                    'alt'     => $news->title,
                    "role"    => MediaHelper::ROLE_NEWS_FEATURE_IMAGE,
                ]
            )
            ->toMediaCollection($news->media_collection_name);
    }

    private function addFeatureImageMobile(News $news): void
    {
        $imageUrl = asset("uploads/images/news/story-feature-image-mobile.png");

        if ($news->newsType?->name == NewsHelper::NEWS_TYPE_VIDEO) {
            $imageUrl = asset("uploads/images/news/video-feature-image-mobile.png");
        }

        if ($news->newsType?->name == NewsHelper::NEWS_TYPE_IMAGE_GALLERY) {
            $imageUrl = asset("uploads/images/news/image-gallery-feature-image-mobile.png");
        }

        $imageExtension = pathinfo($imageUrl, PATHINFO_EXTENSION);
        $imageFileName  = MediaHelper::generateMediaName($news->title, $imageExtension, 200);
        $news->addMediaFromUrl($imageUrl)
            ->usingName($news->title)
            ->usingFileName($imageFileName)
            ->withCustomProperties(
                [
                    'caption' => $news->title,
                    'alt'     => $news->title,
                    "role"    => MediaHelper::ROLE_NEWS_FEATURE_IMAGE_MOBILE,
                ]
            )
            ->toMediaCollection($news->media_collection_name);
    }

}
