<?php
use Illuminate\Routing\Router;

$router->group(['prefix' => '/folders','middleware' => ['auth:api']], function (Router $router) {

  $router->bind('folder', function ($id) {
    return app(\Modules\Media\Repositories\FileRepository::class)->find($id);
  });

  $router->bind('folderByName', function ($name) {
    return app(\Modules\Media\Repositories\FileRepository::class)->findByAttributes(["filename" => $name]);
  });


  $router->get('/', [
    'as' => 'api2.media.folders.index',
    'uses' => 'NewApi\FolderApiController@index',
    //'middleware' => 'auth-can:media.folders.index'
  ]);
  $router->get('/all-nestable', [
    'as' => 'api2.media.folders.all-nestable',
    'uses' => 'NewApi\FolderApiController@allNestable',
    //'middleware' => 'auth-can:media.folders.index'
  ]);

  $router->get('/breadcrumb/{folderByName}', [
    'as' => 'api2.media.folders.breadcrumb',
    'uses' => 'NewApi\FolderApiController@breadcrumb',
    //'middleware' => 'auth-can:media.folders.index'
  ]);

  $router->post('/', [
    'as' => 'api2.media.folders.create',
    'uses' => 'NewApi\FolderApiController@create',
    //'middleware' => 'auth-can:media.folders.create'
  ]);

  $router->put('/{folder}', [
    'as' => 'api2.media.folders.update',
    'uses' => 'NewApi\FolderApiController@update',
    //'middleware' => 'auth-can:media.folders.edit'
  ]);

  $router->delete('/{folder}', [
    'as' => 'api2.media.folders.delete',
    'uses' => 'NewApi\FolderApiController@delete',
    //'middleware' => 'auth-can:media.folders.destroy'
  ]);


});
