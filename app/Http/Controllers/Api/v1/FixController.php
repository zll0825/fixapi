<?php

namespace App\Http\Controllers\Api\v1;

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

class FixController extends Controller
{
    public function hangup(){
        $payload = app('request')->all();
        $user = JWTAuth::parseToken()->authenticate();
        $id = $payload['id'];
        $reason = $payload['reason'];
        $tmp = DB::table('repairrecords')->where(['stateid'=>3,'id'=>$id,'employeename'=>$user->name])->orWhere(['stateid'=>2,'id'=>$id,'employeename'=>$user->name])->first();
        if(!$tmp){
            return ['status_code'=>'422','msg'=>'这不是您抢的单，您不能挂起！'];
        }
        $res = DB::table('repairrecords')->where('id',$id)->update(['stateid'=>6,'employeename'=>$user->name,'hangupreason'=>$reason]);
        if($res){
            return ['status_code'=>'200', 'msg'=>'挂起成功！'];
        } else {
            return ['status_code'=>'401', 'msg'=>'挂起失败！'];
        }
    }

    public function complete(){
        $payload = app('request')->all();
        $user = JWTAuth::parseToken()->authenticate();
        $id = $payload['id'];
        $tmp = DB::table('repairrecords')->where(['id'=>$id,'employeename'=>$user->name])->first();
        if(!$tmp){
            return ['status_code'=>'421','msg'=>'这不是您抢的单，您不能完成！'];
        }
        $completetime = date('Y-m-d H:i:s', time());
        $res = DB::table('repairrecords')->where('id',$id)->update(['stateid'=>4,'completename'=>$user->name,'completetime'=>$completetime]);
        if($res){
            return ['status_code'=>'200', 'msg'=>'提交成功！'];
        } else {
            return ['status_code'=>'401', 'msg'=>'提交失败！'];
        }
    }

    public function rush(){
        $payload = app('request')->all();
        $user = JWTAuth::parseToken()->authenticate();
        $id = $payload['id'];
        $tmp = DB::table('repairrecords')->where(['id'=>$id,'employeename'=>''])->first();
        if(!$tmp){
            return ['status_code'=>'420','msg'=>'订单已经有人抢了！'];
        }
        $res = DB::table('repairrecords')->where('id',$id)->update(['stateid'=>3,'employeename'=>$user->name]);
        if($res){
            return ['status_code'=>'200', 'msg'=>'抢单成功！'];
        } else {
            return ['status_code'=>'401', 'msg'=>'抢单失败！'];
        }
    }

    public function myrush(){
        $payload = app('request')->all();
        $user = JWTAuth::parseToken()->authenticate();
        $data = DB::table('repairrecords')->where('employeename', $user->name)->orderBy('applytime','desc')->get();
        return ['status_code'=>'200', 'data'=>$data];
    }
}
