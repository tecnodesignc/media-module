<?php

namespace Modules\Media\Services;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Entities\File;
use Modules\Media\Jobs\CreateThumbnails;
use Modules\Media\Repositories\FileRepository;
use Modules\Media\Repositories\FolderRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Intervention\Image\Facades\Image;

class MediaService
{
    use DispatchesJobs;

    /**
     * @var FileRepository
     */
    private FileRepository $file;

    private FolderRepository $folder;
    /**
     * @var Factory
     */
    private Factory $filesystem;

    public function __construct(FileRepository $file, Factory $filesystem, FolderRepository $folder)
    {
        $this->file = $file;
        $this->filesystem = $filesystem;
        $this->folder = $folder;
    }

    /**
     * @param UploadedFile $file
     * @param int $parentId
     * @return mixed
     */
    public function store(UploadedFile $file, int $parentId = 0, $path = null): mixed
    {

        $disk = $this->getConfiguredFilesystem();
        if ($path) {
            $parentId = $this->generateFolderPath($path);
        }
        $extension = $file->getMimeType();
        if ($extension === 'image/jpeg' || $file->getClientOriginalExtension() === 'jpg' || $file->getClientOriginalExtension() === 'jpeg') {
            $image = Image::make($file);
            $imageSize = (object)config('encore.media.config.imageSize');
            $watermark = (object)config('encore.media.config.watermark');
            $image->resize($imageSize->width, $imageSize->height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            if ($watermark->activated) {
                $image->insert(url($watermark->url), $watermark->position, $watermark->x, $watermark->y);
            }
        }

        $savedFile = $this->file->createFromFile($file, $parentId);
        $path = $this->getDestinationPath($savedFile->getRawOriginal('path'));
        $stream = fopen($file->getRealPath(), 'r+');
        $this->filesystem->disk($this->getConfiguredFilesystem())->writeStream($path, $stream);
        fclose($stream);
        $this->createThumbnails($savedFile);
        return json_decode(json_encode($savedFile));
    }

    private function generateFolderPath(string $path): string
    {
        // Simula la estructura de carpetas.
        $pathParts = explode('/', $path); // Ejemplo
        $parent_id = 0;
        foreach ($pathParts as $part) {
            $folder = $this->folder->findByAttributes(['filename' => $part]);
            if ($folder === null) {
                $folder = $this->folder->create(['name' => $part, 'parent_id' => $parent_id]);
            }
            $parent_id = $folder->id;
        }
        return $parent_id;
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
