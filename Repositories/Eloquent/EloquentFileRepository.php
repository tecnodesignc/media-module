<?php

namespace Modules\Media\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Core\Repositories\Eloquent\EloquentBaseRepository;
use Modules\Media\Entities\File;
use Modules\Media\Events\FileIsCreating;
use Modules\Media\Events\FileIsUpdating;
use Modules\Media\Events\FileStartedMoving;
use Modules\Media\Events\FileWasCreated;
use Modules\Media\Events\FileWasUpdated;
use Modules\Media\Helpers\FileHelper;
use Modules\Media\Repositories\FileRepository;
use Modules\Media\Repositories\FolderRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EloquentFileRepository extends EloquentBaseRepository implements FileRepository
{
    /**
     * Update a resource
     * @param File $file
     * @param $data
     * @return mixed
     */
    public function update($file, $data)
    {
        event($event = new FileIsUpdating($file, $data));
        $file->update($event->getAttributes());

        $file->setTags(Arr::get($data, 'tags', []));

        event(new FileWasUpdated($file));

        return $file;
    }

    /**
     * Create a file row from the given file
     * @param UploadedFile $file
     * @param int $parentId
     * @return mixed
     */
    public function createFromFile(UploadedFile $file, int $parentId = 0)
    {
        $fileName = FileHelper::slug($file->getClientOriginalName());

        $exists = $this->model->where('filename', $fileName)->where('folder_id', $parentId)->first();

        if ($exists) {
            $fileName = $this->getNewUniqueFilename($fileName);
        }

        $data = [
            'filename' => $fileName,
            'path' => $this->getPathFor($fileName, $parentId),
            'extension' => substr(strrchr($fileName, '.'), 1),
            'mimetype' => $file->getClientMimeType(),
            'filesize' => $file->getFileInfo()->getSize(),
            'folder_id' => $parentId,
            'is_folder' => 0,
        ];

        event($event = new FileIsCreating($data));

        $file = $this->model->create($event->getAttributes());
        event(new FileWasCreated($file));

        return $file;
    }

    private function getPathFor(string $filename, int $folderId)
    {
        if ($folderId !== 0) {
            $parent = app(FolderRepository::class)->findFolder($folderId);
            if ($parent !== null) {
                return $parent->path->getRelativeUrl() . '/' . $filename;
            }
        }

        return config('encore.media.config.files-path') . $filename;
    }

    public function destroy($file)
    {
        $file->delete();
    }

    /**
     * Find a file for the entity by zone
     * @param $zone
     * @param object $entity
     * @return object
     */
    public function findFileByZoneForEntity($zone, $entity)
    {
        foreach ($entity->files as $file) {
            if ($file->pivot->zone == $zone) {
                return $file;
            }
        }

        return '';
    }

    /**
     * Find multiple files for the given zone and entity
     * @param zone $zone
     * @param object $entity
     * @return object
     */
    public function findMultipleFilesByZoneForEntity($zone, $entity)
    {
        $files = [];
        foreach ($entity->files as $file) {
            if ($file->pivot->zone == $zone) {
                $files[] = $file;
            }
        }

        return new Collection($files);
    }

    /**
     * @param $fileName
     * @return string
     */
    private function getNewUniqueFilename($fileName)
    {
        $fileNameOnly = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $models = $this->model->where('filename', 'LIKE', "$fileNameOnly%")->get();

        $versionCurrent = $models->reduce(function ($carry, $model) {
            $latestFilename = pathinfo($model->filename, PATHINFO_FILENAME);

            if (preg_match('/_([0-9]+)$/', $latestFilename, $matches) !== 1) {
                return $carry;
            }

            $version = (int)$matches[1];

            return ($version > $carry) ? $version : $carry;
        }, 0);

        return $fileNameOnly . '_' . ($versionCurrent + 1) . '.' . $extension;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function serverPaginationFilteringFor(Request $request)
    {
        $media = $this->allWithBuilder();

        $media->orderBy('is_folder', 'desc');
        $media->where('folder_id', $request->get('folder_id', 0));

        if ($request->get('search') !== null) {
            $term = $request->get('search');
            $media->where('filename', 'LIKE', "%{$term}%");
        }

        if ($request->get('order_by') !== null && $request->get('order') !== 'null') {
            $order = $request->get('order') === 'ascending' ? 'asc' : 'desc';

            $media->orderBy($request->get('order_by'), $order);
        } else {
            $media->orderBy('created_at', 'desc');
        }

        return $media->paginate($request->get('per_page', 10));
    }

    /**
     * @param int $folderId
     * @return Collection
     */
    public function allChildrenOf(int $folderId): Collection
    {
        return $this->model->where('folder_id', $folderId)->get();
    }

    public function findForVirtualPath(string $path)
    {
        $prefix = config('encore.media.config.files-path');

        return $this->model->where('path', $prefix . $path)->first();
    }

    public function allForGrid(): Collection
    {
        return $this->model->where('is_folder', 0)->get();
    }

    public function move(File $file, File $destination): File
    {
        $previousData = [
            'filename' => $file->filename,
            'path' => $file->path,
        ];

        $this->update($file, [
            'path' => $this->getPathFor($file->filename, $destination->id),
            'folder_id' => $destination->id,
        ]);

        event(new FileStartedMoving($file, $previousData));

        return $file;
    }


    public function getItemsBy($params = false)
    {
        /*== initialize query ==*/
        $query = $this->model->query();
        $query->orderBy('is_folder', 'desc');
        /*== RELATIONSHIPS ==*/
        if (in_array('*', $params->include)) {//If Request all relationships
            $query->with(["createdBy"]);
        } else {//Especific relationships
            $includeDefault = [];//Default relationships
            if (isset($params->include))//merge relations with default relationships
                $includeDefault = array_merge($includeDefault, $params->include);
            $query->with($includeDefault);//Add Relationships to query
        }

        /*== FILTERS ==*/
        if (isset($params->filter)) {
            $filter = $params->filter;//Short filter

            //Filter by date
            if (isset($filter->date)) {
                $date = $filter->date;//Short filter date
                $date->field = $date->field ?? 'created_at';
                if (isset($date->from))//From a date
                    $query->whereDate($date->field, '>=', $date->from);
                if (isset($date->to))//to a date
                    $query->whereDate($date->field, '<=', $date->to);
            }

            //Order by
            if (isset($filter->order)) {
                $orderByField = $filter->order->field ?? 'is_Folder';//Default field
                $orderWay = $filter->order->way ?? 'desc';//Default way
                $query->orderBy($orderByField, $orderWay);//Add order to query
            } else {
                $query->orderBy('media__files.created_at', 'desc');//Add order to query
            }

            //folder id
            if (isset($filter->folder) && (string)$filter->folder != "") {
                $query->where('folder_id', $filter->folder);

            }

            //folder name
            if (isset($filter->folderName) && $filter->folderName != "Home") {

                $folder = \DB::table("media__files as files")
                    ->where("is_folder", true)
                    ->where("filename", $filter->folderName)
                    ->first();

                if (isset($folder->id)) {
                    $query->where('folder_id', $filter->folderId ?? $folder->id);
                }
            }


            //is Folder
            if (isset($filter->zone)) {
                $filesIds = \DB::table("media__imageables as imageable")
                    ->where('imageable.zone', $filter->zone)
                    ->where('imageable.imageable_id', $filter->entityId)
                    ->where('imageable.imageable_type', $filter->entity)
                    ->get()->pluck("file_id")->toArray();
                $query->whereIn("id", $filesIds);
            }

            //add filter by search
            if (isset($filter->search) && $filter->search) {
                //find search in columns
                $query->where(function ($query) use ($filter) {
                    $query->where('id', 'like', '%' . $filter->search . '%')
                        ->orWhere('filename', 'like', '%' . $filter->search . '%')
                        ->orWhere('updated_at', 'like', '%' . $filter->search . '%')
                        ->orWhere('created_at', 'like', '%' . $filter->search . '%');
                });
            }

        }

        $this->validateIndexAllPermission($query, $params);

        /*== FIELDS ==*/
        if (isset($params->fields) && count($params->fields))
            $query->select($params->fields);

        /*== REQUEST ==*/
        if (isset($params->page) && $params->page) {
            return $query->paginate($params->take);
        } else {
            $params->take ? $query->take($params->take) : false;//Take
            return $query->get();
        }
    }


    public function getItem($criteria, $params = false)
    {
        //Initialize query
        $query = $this->model->query();

        /*== RELATIONSHIPS ==*/
        if (in_array('*', $params->include)) {//If Request all relationships
            $query->with([]);
        } else {//Especific relationships
            $includeDefault = [];//Default relationships
            if (isset($params->include))//merge relations with default relationships
                $includeDefault = array_merge($includeDefault, $params->include);
            $query->with($includeDefault);//Add Relationships to query
        }
        /*== FIELDS ==*/
        if (isset($params->fields) && count($params->fields))
            $query->select($params->fields);
        /*== FILTER ==*/
        if (isset($params->filter)) {

            $filter = $params->filter;
            if (isset($filter->field))//Filter by specific field
                $field = $filter->field;


            //is Folder
            if (isset($params->filter->field)&&$params->filter->field=='entity_id') {
                $imageable = DB::table('media__imageables')
                    ->where('imageable_id', $criteria)
                    ->whereZone($filter->zone)
                    ->whereImageableType($filter->entity)->first();
                if(!$imageable) return null;
                return  $query->where('id', $imageable->file_id)->first();
            }
        }
        /*== REQUEST ==*/
        return $query->where($field ?? 'id', $criteria)->first();
    }


    public function create($data)
    {
        return $this->model->create($data);
    }


    function validateIndexAllPermission(&$query, $params)
    {
        if (!isset($params->permissions['media.medias.index-all']) || !$params->permissions['media.medias.index-all']) {
            $user = $params->user;
            $query->where('created_by', $user->id);
        }
    }
}
