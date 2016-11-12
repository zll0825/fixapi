<?php

namespace App\Http\Controllers\\Api\v1;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use JWTAuth;
use App\Mario;
use App\Service;
use Cache;
use Tymon\users\Exceptions\JWTException;
use DB;


class MarioController extends Controller
{
    /**
     * 手机验证码登录
     *
     * @return mixed
     */
    public function smsLogin()
    {
         // 验证规则
        $rules = [
            'phonenumber' => ['required', 'min:11', 'max:11'],
            'smscode' => ['required', 'min:6']
        ];

        $payload = app('request')->only('phonenumber', 'smscode');
        $validator = app('validator')->make($payload, $rules);

        //验证手机号是否存在
        $user = Mario::where('phone', $payload['phonenumber'])->first();
        if(!$user) {
            return $this->response->array(['status_code' => '404', 'msg' => 'phonenumber does not exist']);
        }

        // 验证格式
        if ($validator->fails()) {
            return $this->response->array(['status_code' => '401', 'msg' => $validator->errors()]);
        }

        // 手机验证码验证
        if (Cache::has($payload['phonenumber'])) {
           $smscode = Cache::get($payload['phonenumber']);
        
           if ($smscode != $payload['smscode']) {
               return $this->response->array(['status_code' => '404', 'msg' => 'phonenumber smscode error']);
           }
        } else {
           return $this->response->array(['status_code' => '404', 'msg' => 'phonenumber smscode error']);
        }

        // 通过用户实例，获取jwt-token
        $token = JWTAuth::fromUser($user);
        return $this->response->array(['status_code' => '200', 'token' => $token]);
    }
}
