<?php

namespace Modules\Media\Blade;

use Illuminate\Support\Arr;
use Modules\Media\Image\Imagy;

class MediaThumbnailDirective
{
    /**
     * @var string
     */
    private string $path;
    /**
     * @var string
     */
    private string $thumbnail;

    /**
     * @var Imagy
     */
    private Imagy $imagy;

    public function __construct()
    {
        $this->imagy = app(Imagy::class);
    }

    /**
     * @param array $arguments
     * @return string
     */
    public function show(array $arguments): string
    {
        $this->extractArguments($arguments);

        return $this->imagy->getThumbnail($this->path, $this->thumbnail);
    }

    /**
     * Extract the possible arguments as class properties
     * @param array $arguments
     */
    private function extractArguments(array $arguments)
    {
        $this->path = Arr::get($arguments, 0);
        $this->thumbnail = Arr::get($arguments, 1);
    }
}
