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

Route::get('/', function () {
    return view('welcome');
});


$api = app('Dingo\Api\Routing\Router');
//共有接口不需要登录
$api->version('v1', function ($api) {
	// 用户登录验证并返回 Token
    $api->post('/auth/login', 'App\Http\Controllers\Api\V1\AuthenticateController@smsLogin');
    // 发送验证码
	$api->post('/auth/getsmscode', 'App\Http\Controllers\Api\V1\AuthenticateController@getSmsCode');

	// 首页当天维修单
	$api->post('/index', 'App\Http\Controllers\Api\V1\IndexController@index');

    // 维修单详情
    $api->post('/info', 'App\Http\Controllers\Api\V1\IndexController@info');
});


//私有接口需要登录
$api->version('v1', ['middleware' => 'jwt.auth'], function ($api) {
	//返回用户信息
    $api->post('/auth/me', 'App\Http\Controllers\Api\V1\AuthenticateController@myinfo');

    //挂起
    $api->post('/hangup', 'App\Http\Controllers\Api\V1\FixController@hangup');
    //确认完工
    $api->post('/complete', 'App\Http\Controllers\Api\V1\FixController@complete');
    //抢单
    $api->post('/rush', 'App\Http\Controllers\Api\V1\FixController@rush');
    //我的抢单
    $api->post('/myrush', 'App\Http\Controllers\Api\V1\FixController@myrush');

});