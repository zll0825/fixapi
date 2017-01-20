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
        $payload = app('request')->all();
        $connect = [];
        if(isset($payload['token']) && $payload['token']){
            $user = JWTAuth::parseToken()->authenticate();
            if($user){
                $houseid = DB::table('view_customerdetail')->where('customername',$user->name)->lists('houseid');
                $houseid = array_unique($houseid);
                $connect = DB::table('houses')->whereIn('id',$houseid)->get();
                if($connect){
                    foreach ($connect as $v) {
                        $v->ID = $v->id;
                    }
                }
            }
        }
        $notice = DB::table('notice')->orderBy('id', 'desc')->first();
        if($notice){
        	$notice->ID = $notice->id;
        } else {
            $notice = ['content'=>""];
        }
        $pics = ['pic1'=>'/banner/1.jpg','pic2'=>'/banner/2.jpg','pic3'=>'/banner/3.jpg','pic4'=>'/banner/4.jpg'];
        return ['notice'=>$notice, 'connect'=>$connect, 'pics'=>$pics];
    }
}
