<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::match(['get','post'],'/{type}','WeixinController@functionType');
Route::match(['get','post'],'/wapi/{type}','WApiController@functionType');
Route::match(['get','post'],'/spider/{type}','SpiderController@functionType');
Route::match(['get','post'],'/answer/{type}', 'AnswerController@index');