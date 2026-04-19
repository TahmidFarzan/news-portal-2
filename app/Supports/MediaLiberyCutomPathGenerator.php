<?php

namespace App\Supports;

use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLiberyCutomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return "{$this->getBasePath($media)}/";
    }

    public function getPathForConversions(Media $media): string
    {
        return "{$this->getPath($media)}/conversions/";
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return "{$this->getPath($media)}/responsive-images/";
    }

    protected function getBasePath(Media $media): string
    {
        $path = '';
        $prefix = env('MEDIA_LIBRARY_MEDIA_PREFIX');

        if ($prefix !== '') {
            $path = "{$prefix}";
        }

        return "{$path}/{$media->uuid}";
    }
}
