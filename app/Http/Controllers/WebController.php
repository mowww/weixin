<?php

namespace App\Http\Controllers;
use App\Common\HelperClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class WebController extends Controller
{
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
     /**
     * 方法：联系网页授权
     * @param Request $request
     * @return mixed
     */
    public function auth(Request $request){
        dd($request->all());
    }
}
