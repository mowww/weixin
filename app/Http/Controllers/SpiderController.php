<?php

namespace App\Http\Controllers;
use App\Common\HelperClass;
use App\Model\joke;
use App\Model\checkonline_log;
use App\Model\newUrls;
use App\Model\oldUrls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use QL\QueryList;

class SpiderController extends Controller
{
    public $uri = [
        'wenzi' => 'https://www.qiushibaike.com/text/page/',//糗百文本笑话/文字 1-13   
        // '24h'=> 'https://www.qiushibaike.com/hot/page/',//糗百文本笑话/24小时 1-13
        // '8h'=>'https://www.qiushibaike.com/8hr/page/',//糗百文本笑话/热门 1-13
        // 'new'=>'https://www.qiushibaike.com/textnew/page/',//糗百文本笑话/新鲜 1-35
    ];
    public $url = 'https://www.qiushibaike.com';
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
     * 方法：列表入口
     * @param Request $request
     * @return mixed
     */
    public function getStart($request){
        $client = new Client(['verify' => public_path().'/cert.pem']);
        $uri =  [];
        foreach($this->uri as $k => $v){
            $page = 13;
            if($k=='new'){//35页
                $page = 35;
            }
            $uri[$k] = [];
            for($i=1;$i <= $page ;$i++ ){
                $uri[$k][] = $v.$i.'/';
            }
        }
       
        foreach($uri as $k => $v){
            $tmpUri = $v;
            $total = count($v);
             //请求
            $requests = function ($total) use ($client,$tmpUri) {
                foreach ($tmpUri as $key => $uri) {
                    yield function() use ($client, $uri) {
                        //checkonline_log::create(['content'=>$uri]);
                        return $client->getAsync($uri,[
                                'allow_redirects' => [ 
                                        'protocols'=> ['http','https'] 
                                ],
                                'headers' => [
                                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36',
                                    'Cache-Control'=> 'no-cache',//告诉服务器，自己不要读取缓存，要向服务器发起请求
                                    'Accept-Language'=> 'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.6,en;q=0.4',//客户端可以接受的语言类型
                                    'Accept-Encoding'=> 'gzip, deflate, sdch',//接收编码类型
                                    'DNT'=> '1',//数字1代表禁止追踪，0代表接收追踪，null代表空置，没有规定。
                                    'Connection: keep-alive',
                                    'Pragma'=>'no-cache',
                                    'Cookie'=>'_xsrf=2|179be693|8aec545dc4a1bffe3f551512f99f1715|1532676714; _qqq_uuid_="2|1:0|10:1532676714|10:_qqq_uuid_|56:OWZjZTBiNjkxMDMzM2YzODhkNDJkMzc3NDJiM2JiYzgxNDc4NDYwZg==|36bbea80cefb277e3a51d5130d18c8eeafd1b3f65babbe7736e1bc06ae1b3c44"; Hm_lvt_2670efbdd59c7e3ed3749b458cafaa37=1532676731; Hm_lpvt_2670efbdd59c7e3ed3749b458cafaa37=1532676739',
                                    "Referer"=> "https://www.baidu.com/s?wd=%BC%96%E7%A0%81&rsv_spt=1&rsv_iqid=0x9fcbc99a0000b5d7&issp=1&f=8&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&oq=If-None-Match&inputT=7282&rsv_t=3001MlX2aUzape9perXDW%2FezcxiDTWU4Bt%2FciwbikdOLQHYY98rhPyD2LDNevDKyLLg2&rsv_pq=c4163a510000b68a&rsv_sug3=24&rsv_sug1=14&rsv_sug7=100&rsv_sug2=0&rsv_sug4=7283", 
                                ]
                            ]);//用https
                    };
                }
            };
            $pool = new Pool($client, 
                        $requests($total), 
                        [
                            'concurrency' => 1,//请求数
                            'fulfilled'   => function ($response, $index) use($k){
                                $body = $response->getBody();
                                //  checkonline_log::create(['content'=> json_encode((string) $body)]);
                                $html = $body->getContents();
                                $data = $this->list($html,$k);
                                $addData = [];
                                foreach($data as $key => $value){
                                   $newUrl  = $this->url.$value['url'];
                                   checkonline_log::create(['content'=> $newUrl]);
                                   $old = oldUrls::where('url',$newUrl)->count();
                                   $new =newUrls::where('url',$newUrl)->count();
                                   if(!$old && !$new){
                                        $addData[] =  ['url'=>$newUrl];
                                   }
                                }
                                newUrls::insert($addData);
                                // checkonline_log::create(['content'=>json_encode($data)]);
                                // $this->countedAndCheckEnded();
                            },
                            'rejected' => function ($reason, $index){
                                   checkonline_log::create(['content'=>json_encode($reason)]);
                            },
                        ]);
             //开始发送请求
            // Initiate the transfers and create a promise
            $promise = $pool->promise();
            // Force the pool of requests to complete.
            $promise->wait();
            // unset($promise);
        }
        return 1;
    }
    public function countedAndCheckEnded()
    {
        $data['SPIDER_DATETIME']  = '"'.date('Y-m-d H:i:s').'"';//请求时间
        HelperClass::modifyEnv($data);
    }
    public function list($html,$type)
    {
        switch($type){
            case'new':
                //新鲜事列表,采集规则
                $rules =[ 'url' => ['.content-text a','href']];
                break;
            case'wenzi'://文字列表,采集规则
            case'24h'://24小时,采集规则
            case'8h': //热门列表,采集规则
                $rules = ['url' => ['a.text','href']];
                break;
        }
        //列表选择器
         $rang = 'article';
        //采集
        $data = QueryList::html($html)->rules($rules)->range($rang)->query()->getData();
        //查看采集结果
        return $data;
    }
}