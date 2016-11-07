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


//webhooks回调地址
Route::any('/pay/webhooks','Api\V1\PayController@webhooks');

$api = app('Dingo\Api\Routing\Router');
//共有接口不需要登录
$api->version('v1', function ($api) {
	// 用户登录验证并返回 Token
    $api->post('/auth/login', 'App\Http\Controllers\Api\V1\AuthenticateController@smsLogin');
    // 发送验证码
	$api->post('/auth/getsmscode', 'App\Http\Controllers\Api\V1\AuthenticateController@getSmsCode');

	// 首页接口返回通知和联系方式
	$api->post('/index', 'App\Http\Controllers\Api\V1\IndexController@index');
    // 维修工人
    $api->post('/fix/employee', 'App\Http\Controllers\Api\V1\FixController@employee');
});


//私有接口需要登录
$api->version('v1', ['middleware' => 'jwt.auth'], function ($api) {
	//返回用户信息
    $api->post('/auth/me', 'App\Http\Controllers\Api\V1\AuthenticateController@myinfo');

    //用户维修记录
    $api->post('/fix/record', 'App\Http\Controllers\Api\V1\FixController@record');
    //用户的房子
    $api->post('/fix/location', 'App\Http\Controllers\Api\V1\FixController@location');
    //用户的房子
    $api->post('/fix', 'App\Http\Controllers\Api\V1\FixController@fix');


    //投诉建议
    $api->post('/feedback', 'App\Http\Controllers\Api\V1\ToolController@adviceCreate');
    //投诉建议记录
    $api->post('/feedback/list', 'App\Http\Controllers\Api\V1\ToolController@adviceList');

    //未交费记录
    $api->post('/pay/unpay', 'App\Http\Controllers\Api\V1\PayController@unpay');
    //ping++支付接口
    $api->post('/pay','App\Http\Controllers\Api\V1\PayController@payMoney');



});