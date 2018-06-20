<?php

namespace App\Http\Controllers;
use App\Common\HelperClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class BusController extends Controller
{
    public $host = 'http://wxbus.gzyyjt.net/wei-bus-app/';
    public $getByName = 'route/getByName';
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
     * @param int $type
     * @param int $num
     * @param int $rev
     * @return String
     */
    public static function getURL($type , $num, $rev = 0){
        return [
            "http://wxbus.gzyyjt.net/wei-bus-app/routeStation/getByRouteAndDirection/{$num}/{$rev}",
            "http://wxbus.gzyyjt.net/wei-bus-app/runBus/getByRouteAndDirection/{$num}/{$rev}"
        ][$type];
    }

    //公交查询
    public function getRouteIDByNameOnNet($name){
        $curl = new BusClass();
        $curl->url($this->getByName)
             ->data("name=".$name)
             ->addHeader(
                 //'Connection: keep-alive',
                 'Content-Length: '. strlen("name=".$name)
                 //'Accept: */*',
                 //'Origin: http://wxbus.gzyyjt.net',
                 //'X-Requested-With: XMLHttpRequest',
                 //'User-Agent: Mozilla/5.0 (Linux; Android 7.1.2; A0001 Build/NJH47F; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/53.0.2785.49 Mobile MQQBrowser/6.2 TBS/043520 Safari/537.36 MicroMessenger/6.5.13.1080 NetType/WIFI Language/zh_CN',
                 //'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                 //'Referer: http://wxbus.gzyyjt.net/wei-bus-app/route?nickName=&openId=ouz9Ms3zP4gHey9BOp_H1fOLHNng&gzhUser=gh_342e92a92760',
                 //'Accept-Encoding: gzip, deflate',
                 //'Accept-Language: zh-CN,en-US;q=0.8',
                 //'Cookie: realOpenId=ouz9Ms3zP4gHey9BOp_H1fOLHNng; openId=ouz9Ms3zP4gHey9BOp_H1fOLHNng;'
                 //'Cookie: realOpenId=ouz9Ms3zP4gHey9BOp_H1fOLHNng; openId=ouz9Ms3zP4gHey9BOp_H1fOLHNng;'
             )
             ->exec();
        return json_decode($curl->exp(),1);

    }


    public function getRouteIDByNameInDB($name){
        $sql = "select * from busname where name LIKE '%{$name}%' and active !=0 ORDER BY ver DESC";
        //var_dump($sql);
        $res = $this->pdo->query($sql);
        $res = $res->fetchAll();
        //var_dump($res);
        if($res){
            return $res;
        }else{
            $net = $this->getRouteIDByNameOnNet($name);
            //var_dump($net);
            $first = FALSE;
            $data = NULL;
            $time = date("Y-m-d H:i:s");
            //$time = time();
            foreach ($net as $item){
                if($first){
                    $data .= ",";
                }
                //$data .= "(" . $item['i'] . ",'" . $item['n'] . "','" . $time ."')" ;
                $data .= " ( {$item['i']} , '{$item['n']}' , 1 , 1 , '{$time}' ) ";
                $first = TRUE;
            }

            $sql = "INSERT into busname (rid , name , ver , active , updated_at) values".$data;
            echo $sql;
            $res = $this->pdo->exec($sql);
            //var_dump($res);
            //return $net;
            $sql = "select * from busname where name LIKE '%{$name}%' and active !=0 ORDER BY ver DESC";
            //var_dump($sql);
            $res = $this->pdo->query($sql);
            return $res->fetchAll();
        }
    }

    public function getRouteIDByName($name){
        return $this->getRouteIDByNameInDB($name);
    }


    public function getStationOnNet($rid,$rev = 0){
        $curl = new curl();
        $curl->url(self::getURL(0,$rid,$rev))->exec();
        return json_decode($curl,1);
    }

    public function getStationInDB($rid,$rev = 0){

        $res = $this->pdo->query("select * from busstation where rid={$rid} and rev={$rev} and active != 0 ORDER BY num");
        $res = $res->fetchAll();
        if($res){
            return $res;
        }

        $net = $this->getStationOnNet($rid,$rev);

        $sql = "INSERT into busstation (sid,sname,rid,rev, num ,ver , active ,updated_at) values";
        //var_dump($net);
        $data = NULL;
        $time = date("Y-m-d H:i:s");
        //$time = time();
        $first = FALSE;
        $num = 1;
        //$ver = 1;
        foreach ($net['l'] as $item){
            if($first){
                $data .= ",";
            }
            //$data .= "(" . $item['i'] ." , '"  . $item['n'] . "' , " . $rid . " , " . $rev . " , " . $num ." , ' " . $time . "' )";
            $data .= "({$item['i']} , '{$item['n']}' , {$rid} , {$rev} , {$num} , 1 , 1 , '{$time}')";
            $first = TRUE;
            $num++;
        }

        $sql .= $data;

        //var_dump($sql . $data);

        $res = $this->pdo->exec($sql);
        return $res;
        //var_dump($res);

        //var_dump($net);


    }


    public function getBusOnRoadOnNet($rid, $rev = 0){
        $curl = new curl();
        $curl->url(self::getURL(1,$rid,$rev))->exec();
        return json_decode($curl,1);
    }

    public function getBusOnRoadInDB($rid, $rev = 0){

        $net = $this->getBusOnRoadOnNet($rid,$rev);

        $sql = "INSERT INTO busonroad (bid , goout , sid , updated_at, uni) VALUES ";

        $netjsion = json_encode($net);

        //var_dump($net);

        $datetime = date("Y-m-d H:i:s");
        $this->pdo->exec("INSERT INTO bjs(json,time,rid,rev) value('{$netjsion}' , '{$datetime}' , {$rid} , {$rev}) ");

        $data = NULL;
        //$time = date("Y-m-d H:i:s");
        //$time = time();
        $first = FALSE;
        $num = 1;
        $bn =0;

        foreach ($net as $item){

            if($item['bl'] || $item['bbl']){

                if($item['bl']){

                    foreach($item['bl'] as $item2 ){
                        if($first){
                            $data .= ",";
                        }
                        $time = time();
                        $bn++;
                        $bns = (string)$bn;
                        $unirid = sprintf("%05d",$rid);
                        $unirev = (string)$rev;
                        $unigo  = "0";
                        $uninum = sprintf("%02d",$num);
                        $unitime= (string)$time;
                        $uni = $unirev . $unigo . $bns . $unirid . $uninum . $unitime;


                        $data .= "( {$item2['i']} , 0 ,(select id from busstation where rid = {$rid} and rev = {$rev} and num = {$num} and active != 0 LIMIT 1) , '{$time}' , {$uni})";
                        $first = TRUE;

                    }
                    $bn=0;
                }

                if($item['bbl']){

                    foreach($item['bbl'] as $item2){
                        if($first){
                            $data .= ",";
                        }
                        $time = time();
                        $bn++;
                        $bns = (string)$bn;
                        $unirid = sprintf("%05d",$rid);
                        $unirev = (string)$rev;
                        $unigo  = "1";
                        $uninum = sprintf("%02d",$num);
                        $unitime= (string)$time;
                        $uni = $unirev . $unigo . $bns . $unirid . $uninum . $unitime;

                        //$data .= "(" . $rid . " , " . $item2['i'] .  " , " . $num . " , " . 1 .  "," . $rev ." , '" . $time . "')";
                        $data .= "( {$item2['i']} , 1 ,(select id from busstation where rid = {$rid} and rev = {$rev} and num = {$num} and active != 0 LIMIT 1) , '{$time}' , {$uni})";
                        $first = TRUE;

                    }
                    $bn=0;
                }

            }

            $num++;

        }

        $sql .= $data;
        //var_dump($sql);
        $res = $this->pdo->exec($sql);
        if($this->pdo->errorCode()!='00000'){
            $bug = $this->pdo->errorinfo()[2];
            file_put_contents(__DIR__ . '/busbug.txt', $rid."\t".$rev."\n".$bug."\n",FILE_APPEND);
            file_put_contents(__DIR__ . "/bussql.txt", $rid."\t".$rev."\n".$sql."\n",FILE_APPEND);
        }
        return $res;

    }



    public function checkRoute(){

        $sql = "select * from busname where active != 0";

        $resbus = $this->pdo->query($sql);
        $resbus = $resbus->fetchAll();

        foreach ($resbus as $item){

            $id = $item['id'];
            $rid = $item['rid'];
            $name = $item['name'];
            $ver = $item['ver'];
            //$updated = $item['updated_at'];
            $net = $this->getRouteIDByNameOnNet($name);
            $time = date("Y-m-d H:i:s");
	    	if($rid != $net[0]['i']){    
                $this->pdo->exec("update busname set active = 0 where id={$id}");
                $this->pdo->exec("INSERT into busname(rid,name,ver,active,updated_at) value({$net[0]['i']} , '{$net[0]['name']}' , {++$ver} , 1 , '{$time}' )");
	    	}else{    
                $this->pdo->exec("update busname set updated_at = '{$time}' where id = {$id}");
            }

        }

    }


    public function checkStation(){
        $res = $this->pdo->query("select * from busname where active != 0")->fetchAll();

        foreach($res as $item){
/*            $net = $this->getBusOnRoadOnNet($item['rid']);
            foreach ($this->getStationInDB($item['rid']) as $item2){

            }

            $net = $this->getBusOnRoadOnNet($item['rid'],1);
            foreach ($this->getStationInDB($item['rid'],1) as $item2){

            }*/

            $net = $this->getStationOnNet($item['rid'])['l'];
            $net1 = $this->getStationOnNet($item['rid'],1)['l'];
            $res = $this->getStationInDB($item['rid']);
            $res1 = $this->getStationInDB($item['rid'],1);
            $num = 0;
            $time = date("Y-m-d H:i:s");

            foreach($net as $item2){
                if($item2['i'] != $res[$num]['sid'] && $item2['n'] != $res[$num]['sname'] ){
                    echo "{$item2['i']} \t {$res[$num]['sid']} \n {$item2['n']} \t {$res[$num]['sname']}";
                    $this->updateStation( $net , $item['rid'] , 0 , $res[$num]['ver']);
                    break;
                }else{
                   echo $this->pdo->exec("update busstation set updated_at = '{$time}' where id = {$res[$num]['id']}");
                }
                //echo "{$item2['i']} \t {$res[$num]['sid']} \n {$item2['n']} \t {$res[$num]['sname']}";
                $num++;
            }

            $num = 0;

            foreach($net1 as $item2){
                if($item2['i'] != $res1[$num]['sid'] && $item2['n'] != $res1[$num]['sname'] ){
                    echo "{$item2['i']} \t {$res[$num]['sid']} \n {$item2['n']} \t {$res[$num]['sname']}";
                    $this->updateStation( $net1 , $item['rid'] , 1 , $res[$num]['ver']);
                    break;
                }else{
                    echo $this->pdo->exec("update busstation set updated_at = '{$time}' where id = {$res1[$num]['id']}");
                }
                $num++;
            }


        }



    }


    public function updateStation($net,$rid,$rev,$ver){

        echo $this->pdo->exec("update busstation set active = 0 where rid = {$rid} and rev = {$rev} and active != 0");
        $sql = "INSERT into busstation(sid ,sname , rid , rev , num , ver , active , updated_at) value";
        $num = 1;
        ++$ver;
        $time = date("Y-m-d H:i:s");
        $first = FALSE;
        $data = NULL;
        foreach($net as $item){
            if($first){
                $data .= ",";
            }
            $data .= "({$item['i']} , '{$item['n']}' , {$rid} , {$rev} , {$num} , {$ver} , 1 , '{$time}')";
            $first = TRUE;
            $num++;
        }


        $sql .= $data;

        return $this->pdo->exec($sql);


    }
}
