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

//mobile
Route::get('users/password', 'UserController@changePassword');

//api
Route::group(['domain' => env('APP_API_PREFIX')], function () {
	Route::group(['prefix' => 'v1.0/users'], function () {
		Route::post('verification', 'Api\UserController@sendVerification');
		Route::post('access_token', 'Api\UserController@login');
		Route::post('/', 'Api\UserController@register');
		Route::post('password', ['uses' => 'Api\UserController@changePassword', 'as' => 'password']);
	});
});
