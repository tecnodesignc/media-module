<?php

namespace Modules\Media\Events;

use Modules\Media\Entities\File;

class FileStartedMoving
{
    /**
     * @var File
     */
    public File $file;
    /**
     * @var array
     */
    public array $previousData;

    public function __construct(File $file, array $previousData)
    {
        $this->file = $file;
        $this->previousData = $previousData;
    }
}
