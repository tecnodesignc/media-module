<?php

namespace Modules\Media\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Core\Repositories\BaseRepository;
use Modules\Media\Entities\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileRepository extends BaseRepository
{
    /**
     * Create a file row from the given file
     * @param  UploadedFile $file
     * @param int $parentId
     * @return mixed
     */
    public function createFromFile(UploadedFile $file, int $parentId = 0): mixed;

    /**
     * Find a file for the entity by zone
     * @param string $zone
     * @param object $entity
     * @return object
     */
    public function findFileByZoneForEntity(string $zone, object $entity): object;

    /**
     * Find multiple files for the given zone and entity
     * @param string $zone
     * @param object $entity
     * @return object
     */
    public function findMultipleFilesByZoneForEntity(string $zone, object $entity): object;

    /**
     * @param Request $request
     * @return mixed
     */
    public function serverPaginationFilteringFor(Request $request): mixed;

    /**
     * @param int $folderId
     * @return Collection
     */
    public function allChildrenOf(int $folderId) : Collection;

    public function findForVirtualPath(string $path);

    public function allForGrid() : Collection;

    public function move(File $file, File $destination) : File;


}
