<?php

namespace Modules\Media\Http\Controllers\Api\NewApi;

// Requests & Response
use Illuminate\Support\Facades\Auth;
use Modules\Media\Entities\File;
use Modules\Media\Events\FileWasUploaded;
use Modules\Media\Http\Requests\CreateFolderRequest;
use Modules\Media\Http\Requests\LogRequest;
use Illuminate\Http\Request;
// Base Api
use Modules\Core\Http\Controllers\Api\BaseApiController;
// Transformers
use Modules\Media\Transformers\NewTransformers\MediaTransformer;
// Repositories
use Modules\Media\Repositories\FolderRepository;


class FolderApiController extends BaseApiController
{
    private $folder;
    private $breadcrumb = [
        //0 => 'Home',
    ];


    public function __construct(FolderRepository $folder)
    {
        $this->folder = $folder;
    }

    /**
     * GET ITEMS
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            //Validate permissions
            $this->validatePermission($request, 'media.folders.index');

            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //Request to Repository
            $dataEntity = $this->folder->getItemsBy($params);

            //Response
            $response = [
                "data" => MediaTransformer::collection($dataEntity)
            ];

            //If request pagination add meta-page
            $params->page ? $response["meta"] = ["page" => $this->pageTransformer($dataEntity)] : false;
        } catch (\Exception $e) {
            Log::Error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    /**
     * GET BREADCRUMB
     *
     * @param $criteria
     * @return mixed
     */
    public function breadcrumb(File $folder, Request $request)
    {
        try {
            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            if (isset($params->filter->folderId) && $params->filter->folderId != $folder->id) {
                $folder = $this->folder->findFolder($params->filter->folderId);
            }

            if ($folder->folder_id !== 0) {
                $this->breadcrumb[] = ['id' => $folder->id, 'name' => $folder->filename];
            }

            $this->makeBreadcrumb($folder);

            $this->breadcrumb[] = ['id' => 0, 'name' => 'Home'];

            //Response
            $response = ["data" => array_reverse($this->breadcrumb)];

        } catch (\Exception $e) {
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
    }

    private function makeBreadcrumb($folder)
    {
        if ($folder->parent_folder === null) {
            return;
        }

        $this->breadcrumb[] = ['id' => $folder->parent_folder->id, 'name' => $folder->parent_folder->filename];

        if ($folder->parent_folder->folder_id !== 0) {
            $this->makeBreadcrumb($folder->parent_folder);
        }
    }

    /**
     * CREATE A ITEM
     *
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request)
    {
        \DB::beginTransaction();
        try {
            $user = Auth::user();
            $this->validatePermission($request, 'media.folders.create');
            //Get data
            $data = $request->input('attributes') ?? [];//Get data
            $params = $this->getParamsRequest($request);
            //Validate Request
            $this->validateRequestApi(new CreateFolderRequest((array)$data));
            //Create item
            $folder = $this->folder->create($data);

            $folder->created_by = $user->hasAccess('media.batchs.assign') ? $data->user_id : $params->user->id;
            $folder->save();

            event(new FileWasUploaded($folder));

            //Response
            $response = ["data" => ['msg' => trans('media::folders.folder was created')]];
            \DB::commit(); //Commit to Data Base
        } catch (\Exception $e) {
            \Log::error($e);
            \DB::rollback();//Rollback to Data Base
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }
        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    /**
     * UPDATE ITEM
     *
     * @param $criteria
     * @param Request $request
     * @return mixed
     */
    public function update(File $folder, Request $request)
    {
        \DB::beginTransaction(); //DB Transaction
        try {
            $this->validatePermission($request, 'media.folders.edit');
            //Get data
            $data = $request->input('attributes');


            //Validate Request
            $this->validateRequestApi(new CreateFolderRequest((array)$data));

            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //Request to Repository
            $this->folder->update($folder, $data);

            //Response
            $response = ["data" => ['msg' => trans('media::folders.folder was updated')]];
            \DB::commit();//Commit to DataBase
        } catch (\Exception $e) {
            \Log::error($e);
            \DB::rollback();//Rollback to Data Base
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    /**
     * DELETE A ITEM
     *
     * @param $criteria
     * @return mixed
     */
    public function delete(File $folder, Request $request)
    {
        \DB::beginTransaction();
        try {
            $this->validatePermission($request, 'media.folders.destroy');
            //Get params
            $params = $this->getParamsRequest($request);

            //call Method delete
            $this->folder->destroy($folder);

            //Response
            $response = ["data" => ['msg' => trans('media::folders.folder was deleted')]];
            \DB::commit();//Commit to Data Base
        } catch (\Exception $e) {
            \Log::error($e);
            \DB::rollback();//Rollback to Data Base
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }


    /**
     * GET ALL NESTABLE
     *
     * @return mixed
     */
    public function allNestable(Request $request)
    {
        try {
            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            $array = [];
            $folders = $this->folder->allNested()->nest()->listsFlattened('filename', null, 0, $array, '--- ');

            //Response
            $response = [
                "data" => $folders
            ];

            //If request pagination add meta-page
            $params->page ? $response["meta"] = ["page" => $this->pageTransformer($dataEntity)] : false;
        } catch (\Exception $e) {
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
    }
}
