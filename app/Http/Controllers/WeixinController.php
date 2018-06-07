<?php

namespace App\Http\Controllers;
use App\Common\HelperClass;
use App\Model\checkonline_log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class WeixinController extends Controller
{
    public function __construct(){
         // 获取到微信请求里包含的几项内容,验证
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
    }
    public function index(){
        if (Input::get('echostr') != null) {
            return Input::get('echostr');
        }else{
            return $this->responseMsg();
        }
    }
    //接收推送信息
    public function responseMsg()
    {
        $postStr =   file_get_contents('php://input');
        if (!empty($postStr)){
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";      
            if($postObj->MsgType=='event'){
                if($postObj->Event == 'CLICK'){
                    if($postObj->EventKey == 'V1001_TODAY_MUSIC'){//今日推荐，点击响应
                        $contentStr = "ropynn.top";
                        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $contentStr);
                         return $resultStr;
                    }
                }
            }
        }else {
            return "success";
        }
    }
    /**
     *   创建公众号菜单
     */
    public function createMenu(){
        $res = HelperClass::getAccessToken();
        if($res['code']==600   ){
            return $res['code'];
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=';
        $param = [
            'button'=>[
                [
                    "type"=>"click",
                    "name"=>"今日推荐",
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

     /**
     *   查询公众号菜单接口
     */
    public function getMenu(){
        $res = HelperClass::getAccessToken();
        if($res['code']==600   ){
            return $res['code'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token={$res['data']}";
        $res1 = HelperClass::curl( $url);
        return $res1;
    }
     /**
     *   记录测试日志
     */
    public function log($data){
        if(is_array($data) || is_object($data)){
           $data =  json_encode($data) ;
        }
        checkonline_log::create(['Content'=>$data]);
    }
  
}
