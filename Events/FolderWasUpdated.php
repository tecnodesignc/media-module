<?php

namespace Modules\Media\Events;

use Modules\Media\Entities\File;

class FolderWasUpdated
{
    /**
     * @var File
     */
    public File $folder;
    /**
     * @var array
     */
    public array $data;
    /**
     * @var array
     */
    public array $previousFolderData;

    public function __construct(File $folder, array $data, array $previousFolderData)
    {
        $this->folder = $folder;
        $this->data = $data;
        $this->previousFolderData = $previousFolderData;
    }
}
