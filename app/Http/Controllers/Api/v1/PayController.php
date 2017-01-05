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

class PayController extends Controller
{
    public function unpay(){
        $user = JWTAuth::parseToken()->authenticate();
        $data = DB::table('view_unprepayment')->where('customername', $user->name)->get();
        $total = 0;
        if($data){
            foreach ($data as $v) {
                $total += $v->fee;
                $v->ID = $v->id;
            }
        }            
        
        return array(['status_code'=>'200','data'=>$data, 'total'=>$total]);
    }

    function payMoney() {

        // api_key 获取方式：登录 [Dashboard](https://dashboard.pingxx.com)->点击管理平台右上角公司名称->开发信息-> Secret Key
        $api_key = 'sk_live_zLm1CSrrDqn5ivLyPOvTKyTS';//正式
        $api_key = 'sk_test_rL4yr1m14WHKvv5KeDi9e9i9';//测试
        // app_id 获取方式：登录 [Dashboard](https://dashboard.pingxx.com)->点击你创建的应用->应用首页->应用 ID(App ID)
        $app_id = 'app_LmDWf1aDeHCCjTKq';

        // 此处为 Content-Type 是 application/json 时获取 POST 参数的示例
        $payload = app('request')->all();
        if (empty($payload['channel'])) {
            echo 'channel is empty';
            exit();
        }
        $user = JWTAuth::parseToken()->authenticate();
        $data = DB::table('view_unprepayment')->where('customername', $user->name)->get();
        $total = 0;
        if($data){
            foreach ($data as $v) {
                $total += $v->fee;
            }
        }
        $channel = strtolower($payload['channel']);
        $amount = $total*100;
        $orderNo = 'JF' . substr(time(),4) . mt_rand(1000,9999);
        $subject = isset($payload['subject']) ? $payload['subject']:'嘉恒同心缴费';
        $url = '';
        /**
         * 设置请求签名密钥，密钥对需要你自己用 openssl 工具生成，如何生成可以参考帮助中心：https://help.pingxx.com/article/123161；
         * 生成密钥后，需要在代码中设置请求签名的私钥(rsa_private_key.pem)；
         * 然后登录 [Dashboard](https://dashboard.pingxx.com)->点击右上角公司名称->开发信息->商户公钥（用于商户身份验证）
         * 将你的公钥复制粘贴进去并且保存->先启用 Test 模式进行测试->测试通过后启用 Live 模式
         */
        // 设置私钥内容方式1
        \Pingpp\Pingpp::setPrivateKeyPath(base_path() . '/public/your_rsa_private_key.pem');

        // 设置私钥内容方式2
        // \Pingpp\Pingpp::setPrivateKey(file_get_contents(__DIR__ . '/your_rsa_private_key.pem'));

        /**
         * $extra 在使用某些渠道的时候，需要填入相应的参数，其它渠道则是 array()。
         * 以下 channel 仅为部分示例，未列出的 channel 请查看文档 https://pingxx.com/document/api#api-c-new；
         * 或直接查看开发者中心：https://www.pingxx.com/docs/server/charge；包含了所有渠道的 extra 参数的示例；
         */
        $extra = array();
        switch ($channel) {
            case 'upacp_wap':
                $extra = array(
                    'result_url' => $url// 银联同步回调地址
                );
                break;
            case 'upacp_pc':
                $extra = array(
                    'result_url' => $url// 银联同步回调地址
                );
                break;
            case 'wx_pub':
                $extra = array(
                    'open_id' => 'openidxxxxxxxxxxxx'// 用户在商户微信公众号下的唯一标识，获取方式可参考 pingpp-php/lib/WxpubOAuth.php
                );
                break;
            case 'wx_pub_qr':
                $extra = array(
                    'product_id' => 'gnf'// 为二维码中包含的商品 ID，1-32 位字符串，商户可自定义
                );
                break;
        }


        \Pingpp\Pingpp::setApiKey($api_key);// 设置 API Key
        try {
            $ch = \Pingpp\Charge::create(
                array(
                    //请求参数字段规则，请参考 API 文档：https://www.pingxx.com/api#api-c-new
                    'subject'   => $subject,
                    'body'      => '缴费',
                    'amount'    => $amount,//订单总金额, 人民币单位：分（如订单总金额为 1 元，此处请填 100）
                    'order_no'  => $orderNo,// 推荐使用 8-20 位，要求数字或字母，不允许其他字符
                    'currency'  => 'cny',
                    'extra'     => $extra,
                    'channel'   => $channel,// 支付使用的第三方支付渠道取值，请参考：https://www.pingxx.com/api#api-c-new
                    'client_ip' => $_SERVER['REMOTE_ADDR'],// 发起支付请求客户端的 IP 地址，格式为 IPV4，如: 127.0.0.1
                    'app'       => array('id' => $app_id)
                )
            );

            //整理插入数据
            $data = array();
            $data['customername'] = $user->name;
            $data['ordernumber'] = $orderNo;
            $data['channel'] = $channel;
            // $data['Account'] = $user->Account + floatval($amount);
            $data['created_at'] = date('Y-m-d H:i:s',time());
            $data['ip'] = $_SERVER['REMOTE_ADDR'];
            $data['money'] = $amount;
            $data['flag'] = 0;

            DB::table("order")->insert($data);

            echo $ch;// 输出 Ping++ 返回的支付凭据 Charge
        } catch (\Pingpp\Error\Base $e) {
            // 捕获报错信息
            if ($e->getHttpStatus() != NULL) {
                header('Status: ' . $e->getHttpStatus());
                echo $e->getHttpBody();
            } else {
                echo $e->getMessage();
            }
        }
    }

    public function webhooks() {
        $payload = app('request')->all()['data']['object'];
        $ordernumber = $payload['order_no'];
        $backnumber = $payload['transaction_no'];
        $order = DB::table('order')->where('ordernumber',$ordernumber)->first();
        $user = DB::table('customer')->where('name',$order->customername)->first();
        $order->backnumber = $backnumber;
        $order->flag = 1;

        //整理支付成功需要插入的数据
        DB::beginTransaction();
        try {
            $data = DB::table('view_unprepayment')->where('customername', $order->customername)->get();
            foreach ($data as $key => $value) {
                $customername = $value->customername;
                $addressid = $value->addressid;
                $addresscode = $value->addresscode;
                $addressfullname = $value->address;
                $paytype = 'app';
                $area = $value->buildarea;
                $feestandard = $value->feestandard;
                $fee = $value->fee;
                $feeyear = $value->displayname;
                $receiver = 'app';
                // $receiptvoucherid = $data->customername;
                $receiptdate = date('Y-m-d',time());
                $supplystate = '正常供暖';

                DB::statement("INSERT INTO prepayment (addresscode,customername, addressid,addressfullname,paytype,area,feestandard,fee,feeyear,receiver,receiptvoucherid,receiptdate,supplystate,receiptunit,isprint,balanceid,isdelete) VALUES ('$addresscode','$customername','$addressid','$addressfullname','$paytype','$area','$feestandard',$fee,'$feeyear','$receiver',get_receiptvoucherid(),'$receiptdate','$supplystate','',1,0,0)");
            }
            $order->save();

            DB::commit();
        } catch (Exception $e){
            DB::rollback();
            throw $e;
        }
        if(!isset($e)){
            return 'ok';
        }
    }

    public function payHistory(){
        $payload = app('request')->all();
        $startpage = isset($payload['startpage']) ?  $payload['startpage'] : 1;
        $pagecount = isset($payload['pagecount']) ?  $payload['pagecount'] : 5;
        $skipnum = ($startpage-1)*$pagecount;
        $user = JWTAuth::parseToken()->authenticate();
        $counts = DB::table('prepayment')->where('customername', $user->name)->count();
        $pages = ceil($counts/$pagecount);
        $data = DB::table('prepayment')->where('customername', $user->name)->skip($skipnum)->take($pagecount)->get();
        foreach ($data as $v) {
            $v->ID = $v->id;
        }
        return ['counts'=>$counts, 'pages'=>$pages, 'data'=>$data, 'currentpage'=>$startpage];
    }
}
