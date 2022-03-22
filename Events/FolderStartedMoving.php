<?php

namespace Modules\Media\Events;

use Modules\Media\Entities\File;

class FolderStartedMoving
{
    /**
     * @var File
     */
    public File $folder;
    /**
     * @var array
     */
    public array $previousData;

    public function __construct(File $folder, array $previousData)
    {
        $this->folder = $folder;
        $this->previousData = $previousData;
    }
}
