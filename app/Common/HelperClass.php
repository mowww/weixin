<?php
namespace App\Common;
use App\Model\checkonline_log;
class HelperClass
 { 
      /**
     *   记录测试日志
     */
    public static function log($data){
        if(is_array($data) || is_object($data)){
           $data =  json_encode($data) ;
        }
        checkonline_log::create(['Content'=>$data]);
    }
    /**
     *   修改env配置文件
     */
	public static function modifyEnv($data)
	{
		$envPath = base_path() . DIRECTORY_SEPARATOR . '.env';
		$contentArray = collect(file($envPath, FILE_IGNORE_NEW_LINES));
		//遍历，将配置已有的参数修改
		$contentArray->transform(function ($item) use (&$data){
			 foreach ($data as $key => $value){
				if(str_contains($item, $key)){
                    unset($data[$key]);//移除已修改
					return $key . '=' . $value;
				}
			 }
			 return $item;
         });
		$content = implode($contentArray->toArray(), "\n");
		//配置中没有，添加。
		$str = "\n";
		foreach($data as $k=>$v){
			$str .= "\n".$k.'='. $v;
		}
		$content .= $str;
		\File::put($envPath, $content);
	}
     /**
     *   获取AccessToken
     */
    public static function getAccessToken(){
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
            $res = json_decode(self::curl( $url.http_build_query($param),'GET'),true);
            if($res===false||isset($res['errcode'])){
                return ['code'=>600,'data'=>'getAccessToken接口返回错误','msg'=>$res];
            }
            $data['WEIXIN_ACCESSTOKEN_TIME']  = time();
            $data['WEIXIN_ACCESSTOKEN']  = $res['access_token'];
            $data['WEIXIN_EXPIRES_IN']  =$res['expires_in'];
            self::modifyEnv($data);
            return ['code'=>0,'data'=>$res['access_token']];
        }
    }
     /**
     *   获取JsapiTicket
     */
    public static function getJsapiTicket(){
        $jsapiTicket = env('WEIXIN_JSAPITICKET');
        $expries = env('WEIXIN_EXPIRES_IN');
        $time = env('WEIXIN_JSAPITICKET_TIME');
        //存在jsapiTicket，没有过期
        if( $jsapiTicket && ($time+$expries > time()) ){
            return ['code'=>0,'data'=>$jsapiTicket];
        }else{
            $accessToken = self::getAccessToken()['data'];
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$accessToken}&type=jsapi";
            $res = json_decode(self::curl( $url,'GET'),true);
            if($res===false||isset($res['errcode'])){
                return ['code'=>600,'data'=>'getJsapiTicket接口返回错误','msg'=>$res];
            }
            $data['WEIXIN_JSAPITICKET_TIME']  = time();
            $data['WEIXIN_JSAPITICKET']  = $res['ticket'];
            self::modifyEnv($data);
            return ['code'=>0,'data'=>$res['ticket']];
        }
    }
     /**
     * curl
     */
    public static function curl($url,$wap='POST',$param=[],$time=10)
    {
        if ($wap=='POST') {
            $type=1;
        }else{
            $type=0;
        }
        if($param){
            if( is_array($param) ){
                $param = json_encode($param,JSON_UNESCAPED_UNICODE);
            }else{
                $param = http_build_query($param);
            }
        }
        $handle = curl_init();
        curl_setopt_array(
            $handle,
            array(
                CURLOPT_POST => $type, 
                CURLOPT_TIMEOUT=>$time,
                CURLOPT_POSTFIELDS => $param,
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true
            )
        );
        $response = curl_exec($handle); 
        curl_close($handle);  
        if (empty(json_decode($response))) {
            return $response;
        }
        return $response;
    }
    
}

?>