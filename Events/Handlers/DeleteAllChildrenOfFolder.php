<?php

namespace Modules\Media\Events\Handlers;

use Modules\Dynamicform\Entities\Field;
use Modules\Media\Events\FolderIsDeleting;
use Modules\Media\Image\Imagy;
use Modules\Media\Repositories\FileRepository;
use Modules\Media\Repositories\FolderRepository;

class DeleteAllChildrenOfFolder
{
    /**
     * @var FolderRepository
     */
    private FolderRepository $folder;

    private FileRepository $file;
    private Imagy $imagy;
    public function __construct(FolderRepository $folder, FileRepository $file, Imagy $imagy)
    {
        $this->folder = $folder;
        $this->file = $file;
        $this->imagy = $imagy;
    }

    public function handle(FolderIsDeleting $event)
    {
        $children = $this->folder->allChildrenOf($event->folder);

        foreach ($children as $child) {

            if ($child->is_folder){
                $this->folder->destroy($child);
            }else{

                $this->imagy->deleteAllFor($child);
                $this->file->destroy($child);

            }
        }
    }
}
