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

          if ($validator->fails()&&$secondValidator->fails()) {
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

    public function getOrder(Request $request)
    {
        // user_id:101
        // active_status: received  {received,delivered,cancelled,processed,returned}     // optional

        // limit:25            // { default - 25 } optional
        // offset:0            // { default - 0 } optional
        // sort: id / date_added // { default - id } optional
        // order:DESC/ASC      // { default - DESC } optional        
        // download_invoice:0 // { default - 1 } optional        
  
        $limit = (isset($request->limit) && is_numeric($request->limit) && !empty(trim($request->limit))) ? $request->limit: 25;

        $offset = (isset($request->offset) && is_numeric($request->offset) && !empty(trim($request->offset))) ? $request->offset: 0;

        $sort = (isset($request->sort) && !empty(trim($request->sort))) ? $request->sort: 'o.id';

        $order = (isset($request->order) && !empty(trim($request->order))) ? $request->order: 'DESC';
        $search = (isset($request->search) && !empty(trim($request->search))) ? $request->search: '';
        $validator = Validator::make($request->all(), [
            'user_id'=>'required|numeric',    
            'active_status'=>'',
            'download_invoice'=>'numeric'
   
          ]);

        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] = $validator->errors()->first();;
            $this->response['data'] = array();
        } else {
            $multiple_status =   (isset($request->active_status) && !empty($request->active_status)) ? explode(',', $request->active_status) : false;
            $download_invoice =   (isset($request->download_invoice) && !empty($request->download_invoice)) ? $request->download_invoice: 1;
            $order_details = Order::fetch_orders(false, $request->user_id, $multiple_status, false, $limit, $offset, $sort, $order, $download_invoice, false, false, $search);

            if (!empty($order_details)) {

                $this->response['error'] = false;
                $this->response['message'] = 'Data retrieved successfully';
                $this->response['total'] = $order_details['total'];
                $this->response['data'] = $order_details['order_data'];
            } else {
                $this->response['error'] = true;
                $this->response['message'] = 'No Order(s) Found !';
                $this->response['data'] = array();
            }
        }
        return response()->json($this->response);
    }
  
}
