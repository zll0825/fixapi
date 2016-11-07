<?php

/**
 * 用户验证，获取 token
 */
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use JWTAuth;
use App\User;
use App\Service;
use Cache;
use Tymon\users\Exceptions\JWTException;
use DB;

class AuthenticateController extends Controller
{
    use Helpers;

    /**
    * 获取用户手机验证码
    */
    public function getSmsCode()
    {
        // 获取手机号码
        $payload = app('request')->only('phonenumber');
        $phonenumber = $payload['phonenumber'];

        $action = app('request')->get('action');
        $user = User::where('phone1', $payload['phonenumber'])->orWhere('phone2', $payload['phonenumber'])->first();
        if(!$user) {
            return $this->response->array(['status_code' => '406', 'msg' => 'phonenumber does not exist']);
        }

        // 获取验证码
        $randNum = $this->__randStr(6, 'NUMBER');

        // 验证码存入缓存 10 分钟
        $expiresAt = 20;

        Cache::put($phonenumber, "111111", $expiresAt);

        // // 短信内容
        // $smsTxt = '验证码为：' . $randNum . '，请在 10 分钟内使用！';

        // 发送验证码短信
        // $res = $this->_sendSms($phonenumber, $randNum, $action);
        $res = '1';

        // 发送结果
        if ($res) {
            return $this->response->array(['status_code' => '200', 'msg' => 'Send Sms Success']);
        } else {
            return $this->response->array(['status_code' => '503', 'msg' => 'Send Sms Error']);
        }
    }

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
        $user = User::where('phone1', $payload['phonenumber'])->orWhere('phone2', $payload['phonenumber'])->first();
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


    public function myinfo(){
        $user = JWTAuth::parseToken()->authenticate();
        $user = DB::table('view_customerdetail')->where('customer_id', $user->id)->get();
        return $user;
    }

    /**
     * 随机产生六位数
     *
     * @param int $len
     * @param string $format
     * @return string
     */
    private function __randStr($len = 6, $format = 'ALL')
    {
        switch ($format) {
            case 'ALL':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
                break;
            case 'CHAR':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-@#~';
                break;
            case 'NUMBER':
                $chars = '0123456789';
                break;
            default :
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
                break;
        }
        mt_srand((double)microtime() * 1000000 * getmypid());
        $password = "";
        while (strlen($password) < $len)
            $password .= substr($chars, (mt_rand() % strlen($chars)), 1);
        return $password;
    }

}
