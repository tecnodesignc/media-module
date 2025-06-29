<?php

namespace Modules\Media\Image;

use GuzzleHttp\Psr7\Stream;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Arr;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Modules\Media\Entities\File;
use Modules\Media\ValueObjects\MediaPath;

class Imagy
{
    /**
     * @var ImageManager
     */
    private ImageManager $image;
    /**
     * @var ImageFactoryInterface
     */
    private ImageFactoryInterface $imageFactory;
    /**
     * @var ThumbnailManager
     */
    private ThumbnailManager $manager;

    /**
     * All the different images types where thumbnails should be created
     * @var array
     */
    private array $imageExtensions = ['jpg', 'png', 'jpeg', 'gif'];
    /**
     * @var Factory
     */
    private Factory $filesystem;

    /**
     * @param ImageFactoryInterface $imageFactory
     * @param ThumbnailManager $manager
     */
    public function __construct(ImageFactoryInterface $imageFactory, ThumbnailManager $manager)
    {
        $this->image = app(ImageManager::class);
        $this->filesystem = app(Factory::class);
        $this->imageFactory = $imageFactory;
        $this->manager = $manager;
    }

    /**
     * Get an image in the given thumbnail options
     * @param string $path
     * @param string $thumbnail
     * @param bool $forceCreate
     * @return string
     */
    public function get(string $path, string $thumbnail, bool $forceCreate = false): string
    {
        if (!$this->isImage($path)) {
            return '';
        }

        $filename = $this->getFilenameFor($path, $thumbnail);

        if ($this->returnCreatedFile($filename, $forceCreate)) {
            return $filename;
        }
        if ($this->fileExists($filename) === true) {
            $this->filesystem->disk($this->getConfiguredFilesystem())->delete($filename);
        }

        $mediaPath = (new MediaPath($filename))->getUrl();
        $this->makeNew($path, $mediaPath, $thumbnail);

        return (new MediaPath($filename))->getUrl();
    }

    /**
     * Return the thumbnail path
<<<<<<< HEAD
     * @param string|File $originalImage
     * @param string $thumbnail
=======
     * @param  string|File $originalImage
     * @param  string $thumbnail
>>>>>>> origin/master
     * @return string
     */
    public function getThumbnail($originalImage, $thumbnail)
    {

        if ($originalImage instanceof File) {
            $originalImage = $originalImage->path;
        }
        if (!$this->isImage($originalImage)) {
            if ($originalImage instanceof MediaPath) {
                return $originalImage->getUrl();
            }

            return (new MediaPath($originalImage))->getRelativeUrl();
        }

        $path = $this->getFilenameFor($originalImage, $thumbnail);

        return (new MediaPath($path))->getUrl();
    }

    /**
     * Create all thumbnails for the given image path
     * @param MediaPath $path
     */
    public function createAll(MediaPath $path)
    {
        if (!$this->isImage($path)) {
            return;
        }

        foreach ($this->manager->all() as $thumbnail) {
            $image = $this->image->make($this->filesystem->disk($this->getConfiguredFilesystem())->get($this->getDestinationPath($path->getRelativeUrl())));

            $filename = $this->getFilenameFor($path, $thumbnail);

            foreach ($thumbnail->filters() as $manipulation => $options) {
                $image = $this->imageFactory->make($manipulation)->handle($image, $options);
            }

            $image = $image->stream(pathinfo($path, PATHINFO_EXTENSION), Arr::get($thumbnail->filters(), 'quality', 90));
            $this->writeImage($filename, $image);
        }
    }

    /**
     * Prepend the thumbnail name to filename
     * @param $path
     * @param $thumbnail
     * @return mixed|string
     */
    private function newFilename($path, $thumbnail): string
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);

        return $filename . '_' . $thumbnail . '.' . pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Return the already created file if it exists and force create is false
     * @param string $filename
     * @param bool $forceCreate
     * @return bool
     */
    private function returnCreatedFile(string $filename, bool $forceCreate): bool
    {
        return $this->fileExists($filename) && $forceCreate === false;
    }

    /**
     * Write the given image
     * @param string $filename
     * @param Stream $image
     * @return void
     */
    private function writeImage(string $filename, Stream $image)
    {
        $filename = $this->getDestinationPath($filename);
        $resource = $image->detach();
        $config = [
            'visibility' => 'public',
            'mimetype' => \GuzzleHttp\Psr7\MimeType::fromFilename($filename),
        ];
<<<<<<< HEAD
        if ($this->getConfiguredFilesystem() === "s3") {
            if ($this->fileExists($filename)) {
                return $this->filesystem->disk($this->getConfiguredFilesystem())->writeStream($filename, $resource);
            }
            $this->filesystem->disk($this->getConfiguredFilesystem())->writeStream($filename, $resource);
        } else {
            if ($this->fileExists($filename)) {
                return $this->filesystem->disk($this->getConfiguredFilesystem())->updateStream($filename, $resource, $config);
            }
            $this->filesystem->disk($this->getConfiguredFilesystem())->writeStream($filename, $resource, $config);

        }

=======
        if ($this->fileExists($filename)) {
            return $this->filesystem->disk($this->getConfiguredFilesystem())->updateStream($filename, $resource, $config);
        }
        $this->filesystem->disk($this->getConfiguredFilesystem())->writeStream($filename, $resource, $config);
>>>>>>> origin/master
    }

    /**
     * Make a new image
<<<<<<< HEAD
     * @param MediaPath $path
=======
     * @param MediaPath      $path
>>>>>>> origin/master
     * @param string $filename
     * @param string null $thumbnail
     */
    private function makeNew(MediaPath $path, string $filename, $thumbnail)
    {
        $image = $this->image->make($path->getUrl());

        foreach ($this->manager->find($thumbnail) as $manipulation => $options) {
            $image = $this->imageFactory->make($manipulation)->handle($image, $options);
        }
        $image = $image->stream(pathinfo($path, PATHINFO_EXTENSION));

        $this->writeImage($filename, $image);
    }

    /**
     * Check if the given path is en image
     * @param string $path
     * @return bool
     */
    public function isImage(string $path): bool
    {
        return in_array(pathinfo($path, PATHINFO_EXTENSION), $this->imageExtensions);
    }

    /**
     * Delete all files on disk for the given file in storage
     * This means the original and the thumbnails
     * @param File $file
     * @return bool
     */
    public function deleteAllFor(File $file): bool
    {
        if (!$this->isImage($file->path)) {
            return $this->filesystem->disk($this->getConfiguredFilesystem())->delete($this->getDestinationPath($file->path->getRelativeUrl()));
        }

        $paths[] = $this->getDestinationPath($file->path->getRelativeUrl());

        foreach ($this->manager->all() as $thumbnail) {
            $path = $this->getFilenameFor($file->path, $thumbnail);

            if ($this->fileExists($this->getDestinationPath($path))) {
                $paths[] = (new MediaPath($this->getDestinationPath($path)))->getRelativeUrl();
            }
        }

        return $this->filesystem->disk($this->getConfiguredFilesystem())->delete($paths);
    }

    private function getConfiguredFilesystem()
    {
        return config('encore.media.config.filesystem');
    }

    /**
     * @param $filename
     * @return bool
     */
    private function fileExists($filename): bool
    {
        return $this->filesystem->disk($this->getConfiguredFilesystem())->exists($filename);
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
     * @param MediaPath $path
     * @param Thumbnail|string $thumbnail
     * @return string
     */
    private function getFilenameFor(MediaPath $path, $thumbnail)
    {

<<<<<<< HEAD
        if ($thumbnail instanceof Thumbnail) {
=======
        if ($thumbnail instanceof  Thumbnail) {
>>>>>>> origin/master
            $thumbnail = $thumbnail->name();
        }
        $filenameWithoutPrefix = $this->removeConfigPrefix($path->getRelativeUrl());
        $filename = substr(strrchr($filenameWithoutPrefix, '/'), 1);
        $folders = str_replace($filename, '', $filenameWithoutPrefix);

        if (empty($filename)) {
            return config('encore.media.config.files-path') . $this->newFilename($path, $thumbnail);
        }
        return config('encore.media.config.files-path') . $folders . $this->newFilename($path, $thumbnail);
    }

    /**
     * @param string $path
     * @return string
     */
<<<<<<< HEAD
    private function removeConfigPrefix(string $path): string
=======
    private function removeConfigPrefix(string $path) : string
>>>>>>> origin/master
    {
        $configAssetPath = config('encore.media.config.files-path');

        return str_replace([
            $configAssetPath,
            ltrim($configAssetPath, '/'),
        ], '', $path);
    }
}
