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

class ToolController extends Controller
{
    public function adviceCreate(){
        $payload = app('request')->all();
        $user = JWTAuth::parseToken()->authenticate();
        $data['customername'] = $user->name;
        $data['type'] = $payload['type'];
        $data['content'] = $payload['content'];
        $data['datetime'] = date('Y-m-d H:i:s', time());
        $res = DB::table('feedback')->insert($data);
        if($res){
            return array(['status_code'=>'200', 'msg'=>'提交成功！']);
        } else {
            return array(['status_code'=>'401', 'msg'=>'提交失败！']);
        }

    }

    public function adviceList(){
        $payload = app('request')->all();
        $user = JWTAuth::parseToken()->authenticate();
        $data = DB::table('feedback')->where(['customername'=>$user->name, 'type'=>$payload['type']])->get();
        foreach ($data as $v) {
            $v->ID = $v->id;
        }
        return $data;
    }

    public function comment(){
        $payload = app('request')->all();
        $user = JWTAuth::parseToken()->authenticate();
        $data['comment'] = $payload['comment'];
        $data['commentstar'] = $payload['commentstar'];
        $data['stateid'] = 5;
        $data['returntime'] = date('Y-m-d H:i:s', time());
        $tmp = DB::table('repairrecords')->where(['id'=>$payload['id'],'customername'=>$user->name,'stateid'=>4])->get();
        if(!$tmp){
            return ['status_code'=>'444','msg'=>'您不能评论，可能由于没完工或者不是您的维修单！'];
        }
        $res = DB::table('repairrecords')->where('id',$payload['id'])->update($data);
        if($res){
            return array(['status_code'=>'200', 'msg'=>'提交成功！']);
        } else {
            return array(['status_code'=>'401', 'msg'=>'提交失败！']);
        }

    }
}
