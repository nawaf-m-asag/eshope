<?php

namespace App\Http\Controllers\API;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Fun;

class OrderController extends Controller
{


    public function placeOrder(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            'user_id'=>'required|numeric',    
            'mobile'=>'required|numeric',
            'product_variant_id'=>'required',
            'quantity'=>'required',
            'final_total'=>'required|numeric',
            'promo_code'=>'',
            'is_wallet_used'=>'required|numeric',
            'latitude'=>'numeric',    
            'longitude'=>'numeric',
            'payment_method'=>'required',
            'delivery_date'=>'',
            'delivery_time'=>'',
            'address_id'=>'required',
            'wallet_balance_used'=>'required|numeric'
          ]);
        // if (isset($request->is_wallet_used) && $request->is_wallet_used == '1') {
        //     $validator= Validator::make($request->wallet_balance_used, [
        //         'wallet_balance_used'=>'required|numeric',    
        //     ]); 
        // }
        $settings = Fun::get_settings('system_settings', true);
        $currency = isset($settings['currency']) && !empty($settings['currency']) ? $settings['currency'] : '';
        if (isset($settings['minimum_cart_amt']) && !empty($settings['minimum_cart_amt'])) {
            $secondValidator= Validator::make($request->all(), [
                'total'=>'gte:' . $settings['minimum_cart_amt'] . '',    
            ],
            ['greater_than_equal_to' => 'Total amount should be greater or equal to ' . $currency . $settings['minimum_cart_amt'] . ' total is ' . $currency . $request->total]
        ); 
           
        }

          if ($validator->fails()&&$validatorTwo->fails()) {
            $this->response['error'] = true;
            $this->response['message'] =$validator->messages()->merge($secondValidator->messages())->errors()->first();
            $this->response['data'] = array();
            return response()->json($this->response);
        }
        else {
            $request->is_delivery_charge_returnable = isset($request->delivery_charge) && !empty($request->delivery_charge) && $request->delivery_charge!= '' && $request->delivery_charge> 0 ? 1 : 0;
            $res = Order::place_order($request);
            return response()->json($res);
        }
    }
  
}
