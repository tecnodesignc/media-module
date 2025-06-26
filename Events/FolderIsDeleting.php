<?php

namespace Modules\Media\Events;

use Modules\Media\Entities\File;

class FolderIsDeleting
{
    /**
     * @var File
     */
    public File $folder;

    public function __construct(File $folder)
    {
        $this->folder = $folder;
    }
}
