<?php
namespace App\Common;
class BusClass
{
    public $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
    public $cookie;
    public $url;
    public $data;
    private $ch;
    public $post = FALSE ;
    public $res = NULL;
    protected $header = [
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'Cookie: realOpenId=ouz9Ms-wlCcW1c5Kun1koFuug5kE; openId=ouz9Ms-wlCcW1c5Kun1koFuug5kE;'
    ];
    public function __construct(){
        $ch =curl_init();
        $chopt = [
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_COOKIEFILE => "cookie/cookie",
            CURLOPT_COOKIEJAR => "cookie/cookie",
            CURLOPT_FOLLOWLOCATION => 0,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => $this->header
        ];

        curl_setopt_array($ch,$chopt);
        $this ->ch = $ch;
        return $this;
    }
    public function url($url){
        curl_setopt($this->ch , CURLOPT_URL ,$url);
        $this->url = $url;
        return $this;
    }
    public function cookie($cookie){
        $this->cookie = $cookie;
        return $this;
    }
    public function data($data){
        curl_setopt($this->ch,CURLOPT_POST,1);
        curl_setopt($this->ch,CURLOPT_POSTFIELDS , $data);
        $this->post = TRUE;
        $this->data = $data;
        return $this;
    }
    public function exec(){
        $this->res = curl_exec($this->ch);
        return $this;
    }
    public function __toString()
    {
        return $this->res?:var_dump($this);
    }
    public function exp(){
        return $this->res;
    }
    public function header($header){
        curl_setopt($this->ch,CURLOPT_HTTPHEADER , $header);
        $this->header = $header;
        return $this;
    }
    public function addHeader($header){
        array_push($this->header,$header);
        curl_setopt($this->ch,CURLOPT_HTTPHEADER , $this->header);
        return $this;
    }
    public function ua($ua){
        curl_setopt($this->ch,CURLOPT_USERAGENT , $ua);
        $this->userAgent = $ua;
        return $this;
    }
}