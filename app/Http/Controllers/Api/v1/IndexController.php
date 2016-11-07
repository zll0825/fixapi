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
        $notice = DB::table('notice')->orderBy('id', 'desc')->first();
        $connect = DB::table('connect')->get();
        return ['notice'=>$notice, 'connect'=>$connect];
    }
}
