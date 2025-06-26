<?php

namespace Modules\Media\Events;

use Modules\Media\Entities\File;

class FileWasLinked
{
    /**
     * @var File
     */
    public File $file;
    /**
     * The entity that was linked to a file
     * @var object
     */
    public object $entity;

    /**
     * @param File $file
     * @param object $entity
     */
    public function __construct(File $file, object $entity)
    {
        $this->file = $file;
        $this->entity = $entity;
    }
}
