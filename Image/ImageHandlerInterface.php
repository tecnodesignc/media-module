<?php

namespace Modules\Media\Image;

interface ImageHandlerInterface
{
    /**
     * Handle the image manipulation request
     * @param \Intervention\Image\Image $image
     * @param array $options
     * @return \Intervention\Image\Image
     */
    public function handle(\Intervention\Image\Image $image, array $options): \Intervention\Image\Image;
}
