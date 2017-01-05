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
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;
        $user = JWTAuth::parseToken()->authenticate();
        $stateid = isset($payload['stateid'])?$payload['stateid']:'';
        // $tmp = DB::table('repairrecords')->join('employee','repairrecords.employeename','=','employee.name')->where('customername',$user->name)->first();
        // dd($tmp);
        $counts = DB::table('repairrecords')->leftJoin('employee','repairrecords.employeename','=','employee.name')->join('repairstate', 'repairrecords.stateid', '=', 'repairstate.id')->where('customername', $user->name)->count();
        $pages = ceil($counts/$pagecount);
        $data = DB::table('repairrecords')->leftJoin('employee','repairrecords.employeename','=','employee.name')->join('repairstate', 'repairrecords.stateid', '=', 'repairstate.id')->where('customername', $user->name)->select('repairrecords.id','repairrecords.hangupreason','repairrecords.description','address','employee.phone','employeename','repairstate.name','applytime','stateid')->orderBy('applytime','desc')->skip($skipnum)->take($pagecount)->get();
        if($stateid){
            $counts = DB::table('repairrecords')->join('employee','repairrecords.employeename','=','employee.name')->join('repairstate', 'repairrecords.stateid', '=', 'repairstate.id')->where(['customername' => $user->name, 'stateid' => $stateid])->count();
            $pages = ceil($counts/$pagecount);
            $counts = DB::table('repairrecords')->join('employee','repairrecords.employeename','=','employee.name')->join('repairstate', 'repairrecords.stateid', '=', 'repairstate.id')->where(['customername' => $user->name, 'stateid' => $stateid])->select('repairrecords.id','repairrecords.hangupreason','repairrecords.description','address','employee.phone','employeename','repairstate.name','applytime','stateid')->orderBy('applytime','desc')->skip($skipnum)->take($pagecount)->get();
        }
        foreach ($data as $v) {
            if($v->employeename == null){
                $v->employeename = '';
            }
        }
        return ['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage];
    }

    public function location(){
        $user = JWTAuth::parseToken()->authenticate();
        $data = DB::table('view_customerdetail')->where('customername', $user->name)->get();
        foreach ($data as $v) {
            $v->ID = $v->id;
        }
        return $data;
    }

    public function employee(){
        $payload = app('request')->all();
        $data = [];
        if(isset($payload['address'])){
            $housetypes_id = DB::table('view_customerdetail')->where('address', $payload['address'])->pluck('housetypes_id');
            $houseid = DB::table('housetypes')->where('id', $housetypes_id)->pluck('houses_id');
            $data = DB::table('employee')->where(['houseid'=>$houseid,'status'=>'工作'])->get();
        }
        foreach ($data as $v) {
            $v->ID = $v->id;
        }
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
            if($data['stateid']==1){
                $this->push();
            }
            return array(['status_code'=>'200', 'msg'=>'提交成功！']);
        } else {
            return array(['status_code'=>'401', 'msg'=>'提交失败！']);
        }
    }

    public function push(){
        $client = new \JPush\Client(env('JPUSH_APPKEY'), env('JPUSH_MASTERSECRET'), base_path('storage/logs/jpush.log'));

        $client->push()
            ->setPlatform('all')
            ->addAllAudience()
            ->setNotificationAlert('您有新的维修申请！')
            ->send();
    }
}
