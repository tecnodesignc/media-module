<?php

namespace Modules\Media\Events;

use Modules\Media\Entities\File;

class FileWasUploaded
{
    /**
     * @var File
     */
    public File $file;

    /**
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }
}
