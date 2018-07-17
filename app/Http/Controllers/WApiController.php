<?php

namespace App\Http\Controllers;
use App\Common\HelperClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class WApiController extends Controller
{
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
     *   js-SDK签名算法
     */
    public function getCallbackIp(){
        $res = HelperClass::getAccessToken();
        if($res['code']==600   ){
            return $res['code'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token={$res['data']}";
        $res1 = HelperClass::curl( $url);
        return $res1;
    }
    
  
}
