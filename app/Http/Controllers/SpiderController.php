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
        $uri = $this->uri;
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
                // $uri = 'https://www.qiushibaike.com/';//文章article/120692920
                foreach ($tmpUri as $key => $uri) {
                    yield function() use ($client, $uri) {
                        return $client->getAsync($uri,['allow_redirects' => [ 'protocols'=> ['https'] ]]);//用https
                    };
                }
            };
            $pool = new Pool($client, 
                        $requests($total), 
                        [
                            'concurrency' => 3,//请求数
                            'fulfilled'   => function ($response, $index) use($k){
                                $html = $response->getBody()->getContents();
                                $data = $this->list($html,$k);
                                $addData = [];
                                foreach($data as $key => $value){
                                   $newUrl  = $this->url.$value['url'];
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
                                // $a = $reason;
                                //   checkonline_log::create(['content'=>1]);
                            },
                        ]);
             //开始发送请求
            // Initiate the transfers and create a promise
            $promise = $pool->promise();
            // Force the pool of requests to complete.
            $promise->wait();
            // unset($promise);
        }
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
