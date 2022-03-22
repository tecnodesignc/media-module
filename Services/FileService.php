<?php

namespace Modules\Media\Services;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Modules\Media\Entities\File;
use Modules\Media\Jobs\CreateThumbnails;
use Modules\Media\Repositories\FileRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileService
{
    use DispatchesJobs;

    /**
     * @var FileRepository
     */
    private FileRepository $file;
    /**
     * @var Factory
     */
    private Factory $filesystem;

    public function __construct(FileRepository $file, Factory $filesystem)
    {
        $this->file = $file;
        $this->filesystem = $filesystem;
    }

    /**
     * @param  UploadedFile $file
     * @param int $parentId
     * @return mixed
     */
    public function store(UploadedFile $file, int $parentId = 0): mixed
    {
        $savedFile = $this->file->createFromFile($file, $parentId);

        $path = $this->getDestinationPath($savedFile->getRawOriginal('path'));
        $stream = fopen($file->getRealPath(), 'r+');
        $this->filesystem->disk($this->getConfiguredFilesystem())->writeStream($path, $stream, [
            'visibility' => 'public',
            'mimetype' => $savedFile->mimetype,
        ]);

        $this->createThumbnails($savedFile);

        return $savedFile;
    }

    /**
     * Create the necessary thumbnails for the given file
     * @param File $savedFile
     */
    private function createThumbnails(File $savedFile)
    {
        $this->dispatch(new CreateThumbnails($savedFile->path));
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
