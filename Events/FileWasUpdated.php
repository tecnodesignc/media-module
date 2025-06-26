<?php

namespace Modules\Media\Events;

use Modules\Media\Entities\File;

class FileWasUpdated
{
    /**
     * @var File
     */
    public File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }
}
