<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{

     public  function getShippingMethod(Request $request)
     {
        $res = DB::table('ec_shipping_rules')->selectRaw('id,name,`from`,`to`,price')->where('shipping_id',1)->where('type','base_on_price')->get();
        foreach ($res as $key => $value) {
            $res[$key]->price=($res[$key]->price)<0?strval($res[$key]->price*-1):$res[$key]->price;
        }

        $this->response['error'] = false;
        $this->response['message'] = 'Shipping Method Retrieved Successfully';
        $this->response['data'] = $res;
        return response()->json($this->response);
     }    
    
}