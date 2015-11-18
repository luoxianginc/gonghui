<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('test', 'TestController@index');

Route::get('/', function () {
    return view('welcome');
});

//api
Route::group(['domain' => env('APP_API_PREFIX')], function () {
	Route::group(['prefix' => 'v1.0/users'], function () {
		//Route::get('me/access_token', 'Api\UserController@getAccessToken');
		//Route::get('me', 'Api\UserController@get');
		//Route::post('me', 'Api\UserController@update');
		//Route::get('{userId}', 'Api\UserController@get')->where('userId', '[0-9]{10}');
		Route::post('verification', 'Api\UserController@sendVerification');
		Route::post('access_token', 'Api\UserController@login');
		Route::post('/', 'Api\UserController@register');
		Route::post('password', 'Api\UserController@changePassword');
	});
});
