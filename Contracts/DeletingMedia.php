<?php

namespace Modules\Media\Contracts;

interface DeletingMedia
{
    /**
     * Get the entity ID
     * @return int
     */
    public function getEntityId(): int;

    /**
     * Get the class name the imageables
     * @return string
     */
    public function getClassName(): string;
}
