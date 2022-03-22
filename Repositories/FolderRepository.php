<?php

namespace Modules\Media\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Media\Entities\File;
use Modules\Media\Support\Collection\NestedFoldersCollection;

interface FolderRepository extends BaseRepository
{
    /**
     * Find a folder by its ID
     * @param int $folderId
     * @return File|null
     */
    public function findFolder(int $folderId): ?File;

    /**
     * @param File $folder
     * @return Collection
     */
    public function allChildrenOf(File $folder): Collection;

    /**
     * @return NestedFoldersCollection
     */
    public function allNested() : NestedFoldersCollection;

    /**
     * @param File $folder
     * @param File $destination
     * @return File
     */
    public function move(File $folder, File $destination) : File;

    /**
     * Find the folder by ID or return a root folder
     * which is an instantiated File class
     * @param int $folderId
     * @return File
     */
    public function findFolderOrRoot(int $folderId) : File;
}
