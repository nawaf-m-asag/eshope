<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use Illuminate\Support\Facades\Validator;
class CartController extends Controller
{
    public function addToCart(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id'=>'required|integer',    
            'product_variant_id'=>'required',
            'qty'=>'required',
            'is_saved_for_later'=>'nullable',
           
          ]);


        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] = "error";
            $this->response['data'] = array();
            return response()->json($this->response);
            
        }else {

           // $settings = get_settings('system_settings', true);
            $cart_count = Cart::get_cart_count($request->user_id);
         
            $is_variant_available_in_cart = Cart::is_variant_available_in_cart($request->product_variant_id, $request->user_id);
            
            if (!$is_variant_available_in_cart) {

                if ($cart_count >=12) {
                    $this->response['error'] = true;
                    $this->response['message'] = 'Maximum 12 Item(s) Can Be Added Only!';
                    $this->response['data'] = array();
                    return response()->json($this->response);
                    
                }
            }
            if (!Cart::add_to_cart($request)) {

                $response = Cart::get_cart_total($request->user_id, false);
                $this->response['error'] = false;
                $this->response['message'] = 'Cart Updated !';

                $this->response['data'] = [
                    'total_quantity' => ($_POST['qty'] == 0) ? '0' : strval($_POST['qty']),
                    'sub_total' => strval($response['sub_total']),
                    'total_items' => (isset($response[0]->total_items)) ? strval($response[0]->total_items) : "0",
                    'tax_percentage' => (isset($response['tax_percentage'])) ? strval($response['tax_percentage']) : "0",
                    'tax_amount' => (isset($response['tax_amount'])) ? strval($response['tax_amount']) : "0",
                    'cart_count' => (isset($response[0]->cart_count)) ? strval($response[0]->cart_count) : "0",
                    'max_items_cart' => "12",
                    'overall_amount' => $response['overall_amount'],
                ];
                return response()->json($this->response);
            }
        }


    }   
}
