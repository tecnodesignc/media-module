<?php

namespace Modules\Media\Http\Controllers\Admin;

use Illuminate\Contracts\Config\Repository;
use Modules\Core\Http\Controllers\Admin\AdminBaseController;
use Modules\Media\Entities\File;
use Modules\Media\Http\Requests\UpdateMediaRequest;
use Modules\Media\Image\Imagy;
use Modules\Media\Image\ThumbnailManager;
use Modules\Media\Repositories\FileRepository;

class MediaController extends AdminBaseController
{
    /**
     * @var FileRepository
     */
    private FileRepository $file;
    /**
     * @var Repository
     */
    private Repository $config;
    /**
     * @var Imagy
     */
    private Imagy $imagy;
    /**
     * @var ThumbnailManager
     */
    private ThumbnailManager $thumbnailsManager;

    public function __construct(FileRepository $file, Repository $config, Imagy $imagy, ThumbnailManager $thumbnailsManager)
    {
        parent::__construct();
        $this->file = $file;
        $this->config = $config;
        $this->imagy = $imagy;
        $this->thumbnailsManager = $thumbnailsManager;
    }

    public function index() : \Illuminate\View\View
    {
        $config = $this->config->get('encore.media.config');

        return view('media::admin.index', compact('config'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(): Response
    {
        return view('media.create');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  File     $file
     * @return Response
     */
    public function edit(File $file): Response
    {
        $thumbnails = $this->thumbnailsManager->all();

        return view('media::admin.edit', compact('file', 'thumbnails'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  File               $file
     * @param  UpdateMediaRequest $request
     * @return Response
     */
    public function update(File $file, UpdateMediaRequest $request): Response
    {
        $this->file->update($file, $request->all());

        return redirect()->route('admin.media.media.index')
            ->withSuccess(trans('media::messages.file updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  File     $file
     * @internal param int $id
     * @return Response
     */
    public function destroy(File $file): Response
    {
        $this->imagy->deleteAllFor($file);
        $this->file->destroy($file);

        return redirect()->route('admin.media.media.index')
            ->withSuccess(trans('media::messages.file deleted'));
    }
}
