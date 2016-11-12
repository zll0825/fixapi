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
            }
        }            
        return array(['status_code'=>'200','data'=>$data, 'total'=>$total]);
    }

    function payMoney() {

        // api_key 获取方式：登录 [Dashboard](https://dashboard.pingxx.com)->点击管理平台右上角公司名称->开发信息-> Secret Key
        $api_key = 'sk_live_HW18yLG0ir9S1u5qP4KGa9uH';
        // app_id 获取方式：登录 [Dashboard](https://dashboard.pingxx.com)->点击你创建的应用->应用首页->应用 ID(App ID)
        $app_id = 'app_uz1SuHfHqDuT5u9u';

        // 此处为 Content-Type 是 application/json 时获取 POST 参数的示例
        $payload = app('request')->all();
        if (empty($payload['channel']) || empty($payload['amount'])) {
            echo 'channel or amount is empty';
            exit();
        }
        $channel = strtolower($payload['channel']);
        $amount = $payload['amount'];
        $ProjectID = isset($payload['ProjectID'])?$payload['ProjectID']: null;
        $orderNo = 'CZ' . substr(time(),4) . mt_rand(1000,9999);
        $subject = isset($payload['subject']) ? $payload['subject']:'充值金额';
        $user = JWTAuth::parseToken()->authenticate();
        if($ProjectID){
            $url = "http://ziyawang.com/project/".$ProjectID;
        } else {
            $url = "http://ziyawang.com/ucenter/money/success";
        }

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
            case 'alipay_wap':
                $extra = array(
                    // success_url 和 cancel_url 在本地测试不要写 localhost ，请写 127.0.0.1。URL 后面不要加自定义参数
                    'success_url' => $url,
                    'cancel_url' => 'http://example.com/cancel'
                );
                break;
            case 'alipay_pc_direct':
                $extra = array(
                    // success_url 和 cancel_url 在本地测试不要写 localhost ，请写 127.0.0.1。URL 后面不要加自定义参数
                    'success_url' => $url,
                );
                break;      
            case 'bfb_wap':
                $extra = array(
                    'result_url' => 'http://example.com/result',// 百度钱包同步回调地址
                    'bfb_login' => true// 是否需要登录百度钱包来进行支付
                );
                break;
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
                    'product_id' => 'yabi'// 为二维码中包含的商品 ID，1-32 位字符串，商户可自定义
                );
                break;
            case 'yeepay_wap':
                $extra = array(
                    'product_category' => '1',// 商品类别码参考链接 ：https://www.pingxx.com/api#api-appendix-2
                    'identity_id'=> 'your identity_id',// 商户生成的用户账号唯一标识，最长 50 位字符串
                    'identity_type' => 1,// 用户标识类型参考链接：https://www.pingxx.com/api#yeepay_identity_type
                    'terminal_type' => 1,// 终端类型，对应取值 0:IMEI, 1:MAC, 2:UUID, 3:other
                    'terminal_id'=>'your terminal_id',// 终端 ID
                    'user_ua'=>'your user_ua',// 用户使用的移动终端的 UserAgent 信息
                    'result_url'=>'http://example.com/result'// 前台通知地址
                );
                break;
            case 'jdpay_wap':
                $extra = array(
                    'success_url' => 'http://example.com/success',// 支付成功页面跳转路径
                    'fail_url'=> 'http://example.com/fail',// 支付失败页面跳转路径
                    /**
                    *token 为用户交易令牌，用于识别用户信息，支付成功后会调用 success_url 返回给商户。
                    *商户可以记录这个 token 值，当用户再次支付的时候传入该 token，用户无需再次输入银行卡信息
                    */
                    'token' => 'dsafadsfasdfadsjuyhfnhujkijunhaf' // 选填
                );
                break;
        }


        \Pingpp\Pingpp::setApiKey($api_key);// 设置 API Key
        try {
            $ch = \Pingpp\Charge::create(
                array(
                    //请求参数字段规则，请参考 API 文档：https://www.pingxx.com/api#api-c-new
                    'subject'   => $subject,
                    'body'      => '芽币充值',
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
            $data['UserID'] = $user->userid;
            $data['Type'] = 1;
            $data['Money'] = intval($amount)/10;
            $data['OrderNumber'] = $orderNo;
            // $data['Account'] = $user->Account + floatval($amount);
            $data['created_at'] = date('Y-m-d H:i:s',time());
            $data['timestamp'] = time();
            $data['IP'] = $_SERVER['REMOTE_ADDR'];
            $data['RealMoney'] = $amount;
            $data['Flag'] = 0;

            DB::table("T_U_MONEY")->insert($data);

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
        $OrderNumber = $payload['order_no'];
        $Channel = $payload['channel'];
        $BackNumber = $payload['transaction_no'];
        $money = DB::table('T_U_MONEY')->where('OrderNumber', $OrderNumber)->first();
        if($money){
            $user = User::where('userid', $money->UserID)->first();
            $Account = $user->Account + $money->Money;
            $Operates = '充值' . $money->Money . '芽币';
            $user->Account = $Account;
            DB::beginTransaction();
            try {
                DB::table('T_U_MONEY')->where('OrderNumber', $OrderNumber)->update(['Flag'=>1,'Account'=>$Account, 'BackNumber'=>$BackNumber, 'Channel'=>$Channel, 'Operates'=>$Operates]);
                $user->save();

                DB::commit();
            } catch (Exception $e){
                DB::rollback();
                throw $e;
            }
        }
        // $str = serialize($payload);
        // file_put_contents('./test.txt', $str);
        if(!isset($e)){
            return 'ok';
        }
    }

    public function payHistory(){
        $user = JWTAuth::parseToken()->authenticate();
        $data = DB::table('prepayment')->where('customername', $user->name)->get();
        return array(['status_code'=>'200','data'=>$data]);
    }
}
