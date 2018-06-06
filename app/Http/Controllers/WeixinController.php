<?php

namespace App\Http\Controllers;
use App\Common\HelperClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class WeixinController extends Controller
{
    public function index(){
          // 获取到微信请求里包含的几项内容
          $signature = Input::get('signature');
          $timestamp = Input::get('timestamp');
          $nonce     = Input::get('nonce');
          // weixin 是在微信后台手工添加的 token 的值
          $token = 'weixin';
          // 加工出自己的 signature
          $our_signature = array($token, $timestamp, $nonce);
          sort($our_signature, SORT_STRING);
          $our_signature = implode($our_signature);
          $our_signature = sha1($our_signature);
          
          // 用自己的 signature 去跟请求里的 signature 对比
          if ($our_signature != $signature) {
              return '';
          }
          return Input::get('echostr');
    }
    /**
     *   获取AccessToken
     */
    public function getAccessToken(){
        $accessToken = env('WEIXIN_ACCESSTOKEN');
        $expries = env('WEIXIN_EXPIRES_IN');
        $time = env('WEIXIN_ACCESSTOKEN_TIME');
        //存在accessToken，没有过期
        if( $accessToken && ($time+$expries > time()) ){
            return ['code'=>0,'data'=>$accessToken];
        }else{
            $url = 'https://api.weixin.qq.com/cgi-bin/token?';
            $param['grant_type'] = 'client_credential';
            $param['appid'] = env('WEIXIN_APPID');
            $param['secret'] = env('WEIXIN_APPSECRET');
            $res = json_decode(HelperClass::curl( $url.http_build_query($param),'GET'),true);
            if($res===false||isset($res['errcode'])){
                return ['code'=>600,'data'=>'getAccessToken接口返回错误','msg'=>$res];
            }
            $data['WEIXIN_ACCESSTOKEN_TIME']  = time();
            $data['WEIXIN_ACCESSTOKEN']  = $res['access_token'];
            $data['WEIXIN_EXPIRES_IN']  =$res['expires_in'];
            HelperClass::modifyEnv($data);
            return ['code'=>0,'data'=>$res['access_token']];
        }
    }
    /**
     *   创建公众号菜单
     */
    public function createMenu(){
        $res = $this->getAccessToken();
        if($res['code']==600   ){
            return $res['code'];
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=';
       $param = [
            'button'=>[
                [
                    "type"=>"click",
                    "name"=>"今日歌曲",
                    "key"=>"V1001_TODAY_MUSIC"
                ],
                [
                    "name"=>"菜单",
                    "sub_button"=>[
                        [
                            "type"=>"view",
                            "name"=>"百度搜索",
                            "url"=>"http://www.baidu.com/"
                        ],
                        [
                            "type"=>"click",
                            "name"=>"赞一下我们",
                            "key"=>"V1001_GOOD"
                        ]
                    ]
                ]
            ]
        ];
        $res1 = HelperClass::curl( $url.$res['data'],'POST',$param);
        return $res1;
    }
}
