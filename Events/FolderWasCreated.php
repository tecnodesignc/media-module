<?php

namespace Modules\Media\Events;

use Modules\Media\Entities\File;

class FolderWasCreated
{
    /**
     * @var File
     */
    public File $folder;
    /**
     * @var array
     */
    public array $data;

    public function __construct(File $folder, array $data)
    {
        $this->folder = $folder;
        $this->data = $data;
    }
}
