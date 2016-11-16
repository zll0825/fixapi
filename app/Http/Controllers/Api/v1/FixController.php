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
    public function record(){
        $payload = app('request')->all();
        $user = JWTAuth::parseToken()->authenticate();
        $stateid = isset($payload['stateid'])?$payload['stateid']:'';
        $data = DB::table('repairrecords')->join('repairstate', 'repairrecords.stateid', '=', 'repairstate.id')->where('customername', $user->name)->select('address','name','employeename','applytime')->orderBy('applytime','desc')->get();
        if($stateid){
            $data = DB::table('repairrecords')->join('repairstate', 'repairrecords.stateid', '=', 'repairstate.id')->where(['customername' => $user->name, 'stateid' => $stateid])->select('address','name','employeename','applytime')->orderBy('applytime','desc')->get();
        }
        return $data;
    }

    public function location(){
        $user = JWTAuth::parseToken()->authenticate();
        $data = DB::table('view_customerdetail')->where('customername', $user->name)->get();
        return $data;
    }

    public function employee(){
        $data = DB::table('employee')->get();
        $obj = ['id'=>0,'name'=>'系统分配','phone'=>''];
        array_unshift($data, $obj);
        return $data;
    }

    public function fix(){
        $payload = app('request')->all();
        $user = JWTAuth::parseToken()->authenticate();
        $data['customername'] = $user->name;
        $data['phone'] = $user->phone1?$user->phone1:'';
        $data['phone'] = $data['phone']?$data['phone']:$user->phone2;
        $data['description'] = $payload['description'];
        $data['address'] = $payload['address'];
        $data['applytime'] = date('Y-m-d H:i:s');
        $data['stateid'] = 2;
        if(!isset($payload['name']) || !$payload['name'] || $payload['name'] == '系统分配'){
            // $employeenames = DB::table('employee')->select('name')->get();
            // shuffle($employeenames);
            // $payload['name'] = $employeenames[0]->name;
            $data['stateid'] = 1;
            $payload['name'] = '';
        };
        $data['employeename'] = $payload['name'];
        $res = DB::table('repairrecords')->insert($data);
        if($res){
            return array(['status_code'=>'200', 'msg'=>'提交成功！']);
        } else {
            return array(['status_code'=>'401', 'msg'=>'提交失败！']);
        }
    }
}
