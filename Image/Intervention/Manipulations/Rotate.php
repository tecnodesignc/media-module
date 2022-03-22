<?php

namespace Modules\Media\Image\Intervention\Manipulations;

use Intervention\Image\Image;
use Modules\Media\Image\ImageHandlerInterface;

class Rotate implements ImageHandlerInterface
{
    private array $defaults = [
        'angle' => 45,
        'bgcolor' => '#000000',
    ];

    /**
     * Handle the image manipulation request
     * @param Image $image
     * @param array $options
     * @return \Intervention\Image\Image
     */
    public function handle(Image $image, array $options): Image
    {
        $options = array_merge($this->defaults, $options);

        return $image->rotate($options['angle'], $options['bgcolor']);
    }
}
