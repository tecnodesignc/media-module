<?php

namespace Modules\Media\Image\Intervention\Manipulations;

use Intervention\Image\Image;
use Modules\Media\Image\ImageHandlerInterface;

class Crop implements ImageHandlerInterface
{
    private array $defaults = [
        'width' => '100',
        'height' => '100',
        'x' => null,
        'y' => null,
    ];

    /**
     * Handle the image manipulation request
     * @param Image $image
     * @param array $options
     * @return mixed
     */
    public function handle(Image $image, array $options): mixed
    {
        $options = array_merge($this->defaults, $options);

        return $image->crop($options['width'], $options['height'], $options['x'], $options['y']);
    }
}
