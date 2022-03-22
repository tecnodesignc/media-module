<?php

namespace Modules\Media\Image;

interface ThumbnailManager
{
    /**
     * Register a thumbnail
     * @param string $name
     * @param array $filters
     * @return void
     */
    public function registerThumbnail(string $name, array $filters);

    /**
     * Return all registered thumbnails
     * @return array
     */
    public function all(): array;

    /**
     * Find the filters for the given thumbnail
     * @param string $thumbnail
     * @return array
     */
    public function find(string $thumbnail): array;
}
