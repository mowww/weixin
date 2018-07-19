<?php

namespace App\Http\Controllers;
use App\Common\HelperClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class WApiController extends Controller
{
    public function __construct(){
        header('Access-Control-Allow-Origin:*');
      }
     /**
     * 方法：指向对应函数
     * @param Request $request
     * @return mixed
     */
    public function functionType($type){
        switch ($type) {
            default:
                return $this->$type();
                break;
        }
    }
    
      /**
     *   js-SDK签名算法，获取签名
     *  
     */
    public function getSignPackage(){
        $request = \Request::all();
        $ticket = HelperClass::getJsapiTicket()['data'];
        $url = $request['path'];
        // 生成随机字符串
        $nonceStr = $this->createNoncestr();
        // 生成时间戳
        $timestamp = time();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序 j -> n -> t -> u
        $string = "jsapi_ticket={$ticket}&noncestr={$nonceStr}&tamp={$timestamp}&url={$url}";
        $signature = sha1($string);
        $signPackage = [
            "appId" => Env('WEIXIN_APPID'),
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "signature" => $signature,
        ];
        return  json_encode($signPackage);
    }
    // 创建随机字符串
    public function createNoncestr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for($i = 0; $i < $length; $i ++) {
            $str .= substr ( $chars, mt_rand ( 0, strlen ( $chars ) - 1 ), 1 );
        }
        return $str;
    }
    // 拉取git
    public function gitPull() {
        exec("cd /var/www/250we && git pull origin master ",$output);
        // exec("ls -l",$output);
        print_r($output);
    }
}
