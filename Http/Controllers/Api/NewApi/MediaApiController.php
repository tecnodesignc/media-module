<?php

namespace Modules\Media\Http\Controllers\Api\NewApi;

// Requests & Response
use Modules\Media\Entities\File;
use Modules\Media\Events\FileWasUploaded;
use Modules\Media\Http\Requests\LogRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// Base Api
use Modules\Core\Http\Controllers\Api\BaseApiController;

// Transformers

use Modules\Media\Http\Requests\UpdateMediaRequest;
use Modules\Media\Http\Requests\UploadMediaRequest;
use Modules\Media\Image\Imagy;
use Modules\Media\Services\FileService;
use Modules\Media\Transformers\NewTransformers\MediaTransformer;

// Repositories
use Modules\Media\Repositories\FileRepository;
use function Aws\filter;

class MediaApiController extends BaseApiController
{
    private $file;
    /**
     * @var FileService
     */
    private $fileService;
    /**
     * @var Imagy
     */
    private $imagy;

    public function __construct(FileRepository $file, FileService $fileService, Imagy $imagy)
    {
        $this->file = $file;
        $this->fileService = $fileService;
        $this->imagy = $imagy;
    }


    /**
     * GET ITEMS
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        try {

            $this->validatePermission($request, 'media.medias.index');

            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //Request to Repository
            $media = $this->file->getItemsBy($params);

            //Response
            $response = ["data" => MediaTransformer::collection($media)];

            //If request pagination add meta-page
            $params->page ? $response["meta"] = ["page" => $this->pageTransformer($media)] : false;
        } catch (\Exception $e) {
            \Log::Error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    /**
     * GET A ITEM
     *
     * @param $criteria
     * @return mixed
     */
    public function show($criteria, Request $request)
    {
        try {

            $this->validatePermission($request, 'media.medias.show');

            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);

            //Request to Repository
            $media = $this->file->getItem($criteria, $params);


            //Break if no found item
            if (!$media) throw new \Exception('Item not found', 404);

            //Response
            $response = ["data" => new MediaTransformer($media)];


        } catch (\Exception $e) {
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response, $status ?? 200);
    }

    /**
     * CREATE A ITEM
     *
     * @param Request $request
     * @return mixed
     */
    public function create(UploadMediaRequest $request)
    {
        \DB::beginTransaction();
        try {

            $this->validatePermission($request, 'media.medias.create');
            $data = $request->input('attributes') ?? [];//Get data
            $file = $request->file('file');

            $extension = $file->extension();

            //return [$contentType];
            if ($extension == 'jpeg') {
                $image = \Image::make($request->file('file'));

                $imageSize = (object)config('encore.media.config.imageSize');
                $watermark = (object)config('encore.media.config.watermark');

                $image->resize($imageSize->width, $imageSize->height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                if ($watermark->activated) {
                    $image->insert(url($watermark->url), $watermark->position, $watermark->x, $watermark->y);
                }
                $filePath = $file->getPathName();
                \File::put($filePath, $image->stream('jpg', $imageSize->quality));
            }

            $savedFile = $this->fileService->store($file, $request->input('folder_id'));

            if (is_string($savedFile)) {
                return response()->json([
                    'error' => $savedFile,
                ], 409);
            }
            event(new FileWasUploaded($savedFile));
            //Response
            $response = ["data" => ['msg' => trans('media::messages.file created')]];
            \DB::commit(); //Commit to Data Base
        } catch (\Exception $e) {
            \Log::Error($e);
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
    public function update($criteria, Request $request)
    {
        \DB::beginTransaction(); //DB Transaction
        try {

            $this->validatePermission($request, 'media.medias.edit');

            //Get data
            $data = $request->input('attributes') ?? [];//Get data

            //Validate Request
            $this->validateRequestApi(new CreateMediaRequest($data));

            //Get Parameters from URL.
            $params = $this->getParamsRequest($request);
            //Request to Repository
            $media = $this->file->getItem($criteria, $params);
            //Request to Repository
            $this->media->update($media, $data);

            //Response
            $response = ["data" => trans('media::messages.file updated')];
            \DB::commit();//Commit to DataBase
        } catch (\Exception $e) {
            Log::Error($e);
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
    public function delete($criteria, Request $request)
    {
        \DB::beginTransaction();
        try {
            //Get params
            $params = $this->getParamsRequest($request);

            //Request to Repository
            $media = $this->file->getItem($criteria, $params);

            //call Method delete
            $this->file->destroy($media);

            //Response
            $response = ["data" => trans('blog::common.messages.resource deleted')];
            \DB::commit();//Commit to Data Base
        } catch (\Exception $e) {
            Log::Error($e);
            \DB::rollback();//Rollback to Data Base
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        //Return response
        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }







    /**
     * GET ITEMS
     *
     * @return mixed
     */
    /* public function index(Request $request)
     {
       try {

           $this->validatePermission($request, 'media.medias.index');

         //Get Parameters from URL.
         $params = $this->getParamsRequest($request);

         //Request to Repository
         $dataEntity = $this->file->getItemsBy($params);

         //Response
         $response = [
           "data" => MediaTransformer::collection($dataEntity)
         ];

         //If request pagination add meta-page
         $params->page ? $response["meta"] = ["page" => $this->pageTransformer($dataEntity)] : false;
       } catch (\Exception $e) {
         $status = $this->getStatusError($e->getCode());
         $response = ["errors" => $e->getMessage()];
       }

       //Return response
       return response()->json($response, $status ?? 200);
     }*/


    /**
     * GET A ITEM
     *
     * @param $criteria
     * @return mixed
     */
    /* public function show($criteria, Request $request)
     {
       try {

           $this->validatePermission($request, 'media.medias.show');

         //Get Parameters from URL.
         $params = $this->getParamsRequest($request);

         //Request to Repository
         $dataEntity = $this->file->getItem($criteria, $params);

         //Break if no found item
         if (!$dataEntity) throw new \Exception('Item not found', 404);

         //Response
         $response = ["data" => new MediaTransformer($dataEntity)];

       } catch (\Exception $e) {
         $status = $this->getStatusError($e->getCode());
         $response = ["errors" => $e->getMessage()];
       }

       //Return response
       return response()->json($response, $status ?? 200);
     }*/

    /**
     * CREATE A ITEM
     *
     * @param Request $request
     * @return mixed
     */
    /*  public function create(Request $request)
      {
        \DB::beginTransaction();
        try {

            $this->validatePermission($request, 'media.medias.create');

          //Get Parameters from URL.
          $params = $this->getParamsRequest($request);

          //Get data
          // $data = $request->input('attributes');

          //Validate Request
          $this->validateRequestApi(new UploadMediaRequest($request->all()));
          $file = $request->file('file');
          $contentType = $request["Content-Type"];
          //return [$contentType];
          if($contentType == 'image/jpeg'){


            $image = \Image::make($request->file('file'));

            $imageSize = (Object) config('encore.media.config.imageSize');
            $watermark = (Object) config('encore.media.config.watermark');

            $image->resize($imageSize->width, $imageSize->height, function ($constraint) {
              $constraint->aspectRatio();
              $constraint->upsize();
            });

            if ($watermark->activated) {
              $image->insert($watermark->url, $watermark->position, $watermark->x, $watermark->y);
            }
            $filePath = $file->getPathName();
            \File::put($filePath, $image->stream('jpg',$imageSize->quality));
          }

          $savedFile = $this->fileService->store($file, $request->get('parent_id'));

          $savedFile->created_by =  $params->user->id;
          $savedFile->save();
          if (is_string($savedFile)) throw new \Exception($savedFile, 409);

          event(new FileWasUploaded($savedFile));

          //Response
          $response = ["data" => new MediaTransformer($savedFile)];
          \DB::commit(); //Commit to Data Base
        } catch (\Exception $e) {
          \DB::rollback();//Rollback to Data Base
          $status = $this->getStatusError($e->getCode());
          $response = ["errors" => $e->getMessage()];
        }
        //Return response
        return response()->json($response, $status ?? 200);
      }*/

    /**
     * UPDATE ITEM
     *
     * @param $criteria
     * @param Request $request
     * @return mixed
     */
    /*public function update($criteria, Request $request)
    {
      \DB::beginTransaction(); //DB Transaction
      try {
        //Get data
        $data = $request->input('attributes');


        //Validate Request
        $this->validateRequestApi(new UpdateMediaRequest((array)$data));

        //Get Parameters from URL.
        $params = $this->getParamsRequest($request);

        //Request to Repository
        $this->file->updateBy($criteria, $data, $params);

        //Response
        $response = ["data" => 'Item Updated'];
        \DB::commit();//Commit to DataBase
      } catch (\Exception $e) {
        \DB::rollback();//Rollback to Data Base
        $status = $this->getStatusError($e->getCode());
        $response = ["errors" => $e->getMessage()];
      }

      //Return response
      return response()->json($response, $status ?? 200);
    }*/

    /**
     * DELETE A ITEM
     *
     * @param $criteria
     * @return mixed
     */
    /* public function delete(File $file, Request $request)
     {
       \DB::beginTransaction();
       try {
         //Get params
         $params = $this->getParamsRequest($request);

         //call Method delete
         $this->imagy->deleteAllFor($file);
         $this->file->destroy($file);

         //Response
         $response = ["data" => ""];
         \DB::commit();//Commit to Data Base
       } catch (\Exception $e) {
         \DB::rollback();//Rollback to Data Base
         $status = $this->getStatusError($e->getCode());
         $response = ["errors" => $e->getMessage()];
       }

       //Return response
       return response()->json($response, $status ?? 200);
     }*/

    /**
     * DELETE A ITEM
     *
     * @param $criteria
     * @return mixed
     */
    /* public function downloadFile( $path, Request $request)
     {
       return Storage::response("storage/assets/media/$path");
     }*/

}
