<?php

namespace Modules\Media\Events\Handlers;

use Illuminate\Contracts\Filesystem\Factory;
use Modules\Media\Events\FolderIsDeleting;

class DeleteFolderOnDisk
{
    /**
     * @var Factory
     */
    private Factory $finder;

    public function __construct(Factory $finder)
    {
        $this->finder = $finder;
    }

    /**
     * @param FolderIsDeleting $event
     * @return void
     */
    public function handle(FolderIsDeleting $event)
    {
        $this->finder->disk($this->getConfiguredFilesystem())->deleteDirectory($this->getDestinationPath($event->folder->getOriginal('path')));
    }

    /**
     * @param string $path
     * @return string
     */
    private function getDestinationPath(string $path): string
    {
        if ($this->getConfiguredFilesystem() === 'local') {
            return basename(public_path()) . $path;
        }

        return $path;
    }

    /**
     * @return string
     */
    private function getConfiguredFilesystem(): string
    {
        return config('encore.media.config.filesystem');
    }
}
