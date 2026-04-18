<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait InteractsWithMediaExtend
{
    public function addDirectUrl($directUrl)
    {
        return new class($this, $directUrl) {
            protected $model;
            protected $directUrl;
            protected $name;
            protected $customProperties = [];

            public function __construct($model, $directUrl)
            {
                $this->model = $model;
                $this->directUrl = $directUrl;
            }

            public function usingName($name)
            {
                $this->name = $name;
                return $this;
            }

            public function withCustomProperties(array $properties)
            {
                $this->customProperties = $properties;
                return $this;
            }

            public function toMediaCollection($collectionName = 'default', $diskName = null)
            {
                $record = new Media();
                $record->media_type = "Url";
                $record->model_id = $this->model->id;
                $record->collection_name = $collectionName;
                $record->model_type = $this->model->getMorphClass();

                $record->file_name = '';
                $record->name = $this->name;
                $record->url = $this->directUrl;
                $record->mime_type = 'text/plain';

                $record->disk = $diskName ?? config("media-library.disk_name") ?? '';
                $record->conversions_disk = $diskName ?? config("media-library.disk_name") ?? '';

                $record->size = 0;
                $record->custom_properties = $this->customProperties ?? [];

                $record->manipulations = [];
                $record->responsive_images = [];
                $record->generated_conversions = [];

                $record->save();

                return $record;
            }
        };
    }

    public function addNullModelMedia($uploadfile)
    {
        return new class($this, $uploadfile) {
            protected $model;
            protected $uploadfile;
            protected $name;
            protected $fileName;
            protected $customProperties = [];

            public function __construct($model, $uploadfile)
            {
                $this->model = $model;
                $this->uploadfile = $uploadfile;
            }

            public function usingName($name)
            {
                $this->name = $name;
                return $this;
            }

            public function usingFileName($fileName)
            {
                $this->fileName = $fileName;
                return $this;
            }

            public function withCustomProperties(array $properties)
            {
                $this->customProperties = $properties;
                return $this;
            }

            public function toMediaCollection($collectionName = 'default', $diskName = null)
            {
                $record = new Media();
                $record->uuid = Str::uuid();
                $record->media_type = "Upload";
                $record->model_id = null;
                $record->collection_name = $collectionName;
                $record->model_type = $this->model->getMorphClass();

                $record->file_name = $this->fileName ?? $this->uploadfile->getClientOriginalName();
                $record->name = $this->name ?? null;
                $record->url = null;
                $record->mime_type = $this->uploadfile->getClientMimeType();
                $record->disk = $diskName ?? config("media-library.disk_name") ?? '';
                $record->conversions_disk = $diskName ?? config("media-library.disk_name") ?? '';

                $record->size = $this->uploadfile->getSize();
                $record->custom_properties = $this->customProperties;

                $record->manipulations = [];
                $record->responsive_images = [];
                $record->generated_conversions = [];

                $subDirectoryPath = '';
                if(config("media-library.prefix")){
                    $subDirectoryPath = config("media-library.prefix");
                }
                $subDirectoryPath = "{$subDirectoryPath}/{$record->uuid}";
                $fullPath = "{$subDirectoryPath}/{$record->file_name}";
                Storage::disk($record->disk)->put($fullPath, file_get_contents($this->uploadfile));

                $record->url = Storage::disk($record->disk)->url($fullPath);
                $record->save();

                return $record;
            }
        };
    }
}
