<?php

namespace App\Http\Controllers;
use App\Common\HelperClass;
use App\Model\wechatUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class WebController extends Controller
{
     /**
     * 方法：指向对应函数
     * @param Request $request
     * @return mixed
     */
    public function functionType($type,Request $request){
        $data = $request->all();
        switch ($type) {
            default:
                return $this->$type($data);
                break;
        }
    }
     /**
     * 方法：网页授权, 1.获取code
     * @param Request $request
     * @return mixed
     */
    public function auth($request){
        $appid = env('WEIXIN_APPID');
        $redir_url = urlencode('http://weixin.ropynn.top/web/getBaseInfo');
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redir_url}&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";
        header('location:'.$url);
    }
     /**
     * 方法：获取用户信息
     * @param Request $request
     * @return mixed
     */
    public function getBaseInfo($request){
        $code = $request['code'];
        $appid = env('WEIXIN_APPID');
        $secret = env('WEIXIN_APPSECRET');
        //2.获取授权的access_token
        $url1 = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
        $res = json_decode(HelperClass::curl($url1),true);
        //3.获取用户openid
        $url2  ="https://api.weixin.qq.com/sns/userinfo?access_token={$res['access_token']}&openid={$res['openid']}&lang=zh_CN";
        $res2 = json_decode(HelperClass::curl($url2),true);
        $count = wechatUser::where('openid',$res2['openid'])->count();
        $res2['access_token'] = $res['access_token'];
        $res2['refresh_token'] = $res['refresh_token'];
        $res2['openid'] = $res['openid'];
        unset($res2['privilege']);
        if($count){
            wechatUser::where('openid',$res2['openid'])->update($res2);
        }else{
            wechatUser::create($res2);
        }
        return view('weixin',['data'=>$res2]);
    }
}
