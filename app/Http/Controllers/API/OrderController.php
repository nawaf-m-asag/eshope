<?php

namespace App\Http\Controllers\API;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;


class OrderController extends Controller
{


    public function placeOrder(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            'user_id'=>'required|integer',    
            'mobile'=>'required|integer',
            'product_variant_id'=>'required',
            'quantity'=>'required',
            'final_total'=>'required|integer',
            'promo_code'=>'',
            'is_wallet_used'=>'required|integer',
            'latitude'=>'integer',    
            'longitude'=>'integer',
            'payment_method'=>'required',
            'delivery_date'=>'',
            'delivery_time'=>'',
            'address_id'=>'required',
            'total'=>'required',
          ]);

  
          if (isset($request->is_wallet_used) && $request->is_wallet_used == '1') {
            $validator= Validator::make($request->all(), [
                'wallet_balance_used'=>'required|integer',    
              ]); 
          }

        
          if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] =$validator->errors()->first();
            $this->response['data'] = array();
            return response()->json($this->response);
        }
        else {
            $request->is_delivery_charge_returnable = isset($request->delivery_charge) && !empty($request->delivery_charge) && $request->delivery_charge!= '' && $request->delivery_charge> 0 ? 1 : 0;
            $res = Order::place_order($request);
            return response()->json($res );
        }
    }
  
}
