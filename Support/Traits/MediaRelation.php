<?php

namespace Modules\Media\Support\Traits;

use Modules\Media\Entities\File;
use Modules\Media\Image\Imagy;

trait MediaRelation
{
    /**
     * Make the Many To Many Morph To Relation
     * @return object
     */
    public function files()
    {
        return $this->morphToMany(File::class, 'imageable', 'media__imageables')->withPivot('zone', 'id')->withTimestamps()->orderBy('order');
    }

    /**
     * Make the Many to Many Morph to Relation with specific zone
     * @param string $zone
     * @return object
     */
    public function filesByZone($zone)
    {
        return $this->morphToMany(File::class, 'imageable', 'media__imageables')
            ->withPivot('zone', 'id')
            ->wherePivot('zone', '=', $zone)
            ->withTimestamps()
            ->orderBy('order');
    }

    /**
     * Order and transform all files data
     *
     * @return array
     */
    public function transformerFiles()
    {
        $imagy = app(Imagy::class);
        $files = $this->files;
        $response = [];

        //Transform Files
        foreach ($files as $file) {
            //Get zone name
            $zone = $file->pivot->zone;
            //Transform file
            $fileTransformer = [
                'id' => $file->id,
                'filename' => $file->filename,
                'path' => $file->is_folder ? $file->path->getRelativeUrl() : (string)$file->path,
                'isImage' => $file->isImage(),
                'isFolder' => $file->isFolder(),
                'mediaType' => $file->media_type,
                'createdAt' => $file->created_at,
                'folderId' => $file->folder_id,
                'smallThumb' => $imagy->getThumbnail($file->path, 'smallThumb'),
                'mediumThumb' => $imagy->getThumbnail($file->path, 'mediumThumb'),
                'createdBy' => $file->created_by
            ];
            //Add to response
            if (isset($response[$zone])) array_push($response[$zone], $fileTransformer);
            else $response[$zone] = [$fileTransformer];
        }

        //Validate quantity files per zone
        foreach ($response as $key => $item) if (count($item) == 1) $response[$key] = $item[0];

        //Response
        return $response;
    }
}
