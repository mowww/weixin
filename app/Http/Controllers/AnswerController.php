<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Model\user;
use App\Model\answer;
use Crypt;
class AnswerController extends Controller
{
    public function __construct(){
      header('Access-Control-Allow-Origin:*');
    }
    public function index($type){
      $request = \Request::all();
      switch($type){
          default:
            $data = $this->$type($request);
      }
      return $data ;
    }
    public function login($request){
        if(!isset($request['account'])||!isset($request['password'])){
            return ['code'=>600,'data'=>'请输入：account账号、password密码'];
        }
        $id = user::where('Account',$request['account'])->where('Password',$request['password'])->first();
        if(!$id){
            return ['code'=>600,'data'=>'用户名或密码不正确'];
        }
        $token = Crypt::encrypt(['id'=>$id->id]);
        return    ['code'=>0,'token'=>$token];
    }
    public function register($request){
        if(!isset($request['account'])||!isset($request['password'])){
            return ['code'=>600,'data'=>'请输入：account账号、password密码'];
        }
        $id = user::where('Account',$request['account'])->first();
        if($id){
            return ['code'=>600,'data'=>'用户名已存在'];
        }
        $add['Account'] = $request['account'];
        $add['Password'] = $request['password'];
        $id = user::create($add); 
        $token = Crypt::encrypt(['id'=>$id->id]);
        return    ['code'=>0,'token'=> $token ];
    }
    public function answer($request){
        if(!isset($request['token'])||!isset($request['li'])||!isset($request['answer'])){
            return ['code'=>600,'data'=>'请输入：token、li题号、answer答案'];
        }
        $id =  Crypt::decrypt($request['token'])['id'];
        $an = answer::where('Li',$request['li'])->first();
        $user = user::where('id',$id)->first();
        if($user->Status==2){
            return ['code'=>500,'data'=>'答题失败'];
        }
        //对
        if($an->Answer==$request['answer']){
            $a = '答题正确';
            $update = ['Number'=>$user->Number+1];
        }else{
            $a = '答题错误';
            $update = ['Status'=>2];
        }
        $id = user::where('id',$id)->update($update); 
        return    ['code'=>0,'data'=>$a];
    }
    public function count(){
        $user = user::get()->toArray();
        $a = [];
        foreach($user as $k =>$v){
            $a[] = ['account'=>$v['Account'],'count'=>$v['Number']]; 
        }
        return    ['code'=>0,'data'=>$a];
    }
}