<?php

namespace App\Http\Controllers;
use App\Common\HelperClass;
use Illuminate\Http\Request;
use App\Model\joke;
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
    //处理消息，接收推送信息
    public function responseMsg()
    {
        $postStr =   file_get_contents('php://input');
        if (!empty($postStr)){
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUserName = $postObj->FromUserName;
            $toUserName = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            
            //点击菜单事件
            if($postObj->MsgType=='event'){
                if($postObj->Event == 'CLICK'){
                    $textTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";  
                    if($postObj->EventKey == 'V1001_TODAY_MUSIC'){//今日推荐，点击响应
                        $content = $this->getRandJoke();
                        $resultStr = sprintf($textTpl, $fromUserName, $toUserName, $time, $content);
                         return $resultStr;
                    }
                }
            }else{
                //回复消息
                switch($postObj->MsgType){
                    case "text":{
                        $textTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
                                    <FromUserName><![CDATA[%s]]></FromUserName>
                                    <CreateTime>%s</CreateTime>
                                    <MsgType><![CDATA[%s]]></MsgType>
                                    <Content><![CDATA[%s]]></Content>
                                    </xml>";  
                        $createTime = time();  
                        $msgType = "text";  
                        $content = "接收方:$toUserName";  
                        $content .= "\n发送方:$fromUserName";  
                        $content .= "\n创建时间:$postObj->CreateTime";  
                        $content .= "\n类型:text";  
                        $content .= "\n内容:$postObj->Content";  
                        $content .= "\n消息ID:$postObj->MsgId";  
                        $res = sprintf( $textTpl ,  $fromUserName,$toUserName, $createTime, $msgType, $content);  
                    }
                }
                return $res;
            }

        }else {
            return "success";
        }
    }
     /**
     *   获取一个笑话
     */
    public function getRandJoke(){
        $id = \DB::select(\DB::raw('SELECT
                                jid
                            FROM
                                jid
                            ORDER BY
                                RAND()
                            LIMIT 1'));
        $data = joke::find($id->jid)->toArray();
        $content = $data[0]['content'];
        $content = str_replace("<br>","\n",$content);
        $content = str_replace("糗事百科","天才与渣渣",$content);
        if($data[0]['comment']){
            $comment = json_decode($data[0]['comment'],true);
            $content .= "\n/********评论*******/";
            foreach($comment as $k => $v){
                $content .= "\n{$v['user']}👤：{$v['content']}";
            }
        }
        return $content;
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
                    "name"=>"皮一下",
                    "key"=>"V1001_TODAY_MUSIC"
                ],
                // [
                //     "name"=>"菜单",
                //     "sub_button"=>[
                //         [
                //             "type"=>"view",
                //             "name"=>"百度搜索",
                //             "url"=>"http://www.baidu.com/"
                //         ],
                //         [
                //             "type"=>"click",
                //             "name"=>"赞一下我们",
                //             "key"=>"V1001_GOOD"
                //         ]
                //     ]
                // ],
                [
                    "type"=>"view",
                    "name"=>"授权看电影",
                    "url"=>"http://weixin.ropynn.top/web/auth"
                ],
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
     *   获取微信服务器IP地址
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
