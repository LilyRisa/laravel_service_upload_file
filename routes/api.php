<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::middleware(['ValidationToken'])->group(function () {
    Route::get('checkmiddleware', function () {
        return \response()->json(['result' => 'Your token Invalid!']); // client test token
    });
    Route::post('/upload','UploadController@upload');
});

Route::get('getfile/{token}','UploadController@getFile');
Route::get('getfile/detect_audio/{token}','DetectController@getFile');
Route::get('detect_audio/lyrics/{token}','DetectController@lyrics');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
