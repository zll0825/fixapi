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

class IndexController extends Controller
{
    public function index(){
    	$date = date('Y-m-d');
        $user = JWTAuth::parseToken()->authenticate();
    	$time = [date('Y-m-d H:i:s',strtotime($date.' 00:00:00')),date('Y-m-d H:i:s',strtotime($date.'23:59:59'))];
        // dd($time);
        $self = DB::table('repairrecords')->where(['stateid'=>2,'employeename'=>$user->name])->lists('id');
        $rush = DB::table('repairrecords')->where(['stateid'=>3,'employeename'=>$user->name])->lists('id');
        $poss = DB::table('repairrecords')->where(['stateid'=>1,'employeename'=>null])->orWhere(['stateid'=>1,'employeename'=>''])->lists('id');
        $ids = array_merge($self,$rush,$poss);
        $records = DB::table('repairrecords')->whereBetween('applytime',$time)->whereIn('id',$ids)->orderBy('applytime','desc')->get();
        return ['status_code'=>'200','records'=>$records];
    }

    public function info(){
        $payload = app('request')->all();
    	$id = $payload['id'];
    	$data = DB::table('repairrecords')->where('id',$id)->get();
    	return ['status_code'=>'200', 'data'=>$data];
    }
}
