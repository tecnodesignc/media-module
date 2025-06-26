<?php

namespace Modules\Media\Image;

class ThumbnailManagerRepository implements ThumbnailManager
{
    /**
     * @var array
     */
    private array $thumbnails = [];

    public function registerThumbnail(string $name, array $filters)
    {
        $this->thumbnails[$name] = Thumbnail::make([$name => $filters]);
    }

    /**
     * Return all registered thumbnails
     * @return array
     */
    public function all(): array
    {
        return $this->thumbnails;
    }

    /**
     * Find the filters for the given thumbnail
     * @param string $thumbnail
     * @return array
     */
    public function find(string $thumbnail): array
    {
        foreach ($this->all() as $thumb) {
            if ($thumb->name() === $thumbnail) {
                return $thumb->filters();
            }
        }

        return [];
    }
}
