<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Fun;
use App\Models\Ec_product;

use Illuminate\Support\Facades\DB;
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
            $this->response['message'] = $validator->errors()->first();
            $this->response['data'] = array();
            return response()->json($this->response);
            
        }else {

            $settings = Fun::get_settings('system_settings', true);
            $cart_count = Cart::get_cart_count($request->user_id);
         
            $is_variant_available_in_cart = Cart::is_variant_available_in_cart($request->product_variant_id, $request->user_id);
            
            if (!$is_variant_available_in_cart) {
                
                if ($cart_count >=$settings['max_items_cart']) {
                    $this->response['error'] = true;
                    $this->response['message'] = 'Maximum '.$settings['max_items_cart'].' Item(s) Can Be Added Only!';
                    $this->response['data'] = array();
                    return response()->json($this->response);
                    
                }
            }


        $product_variant_id = explode(',', $request->product_variant_id);
        $qty = explode(',', $request->qty);
    
        $check_current_stock_status = Cart::validate_stock($product_variant_id, $qty);
           
        if (!empty($check_current_stock_status) && $check_current_stock_status['error'] == true) {
            $CSRFToken = csrf_token();
                 $check_current_stock_status['csrfName'] = null;
                 $check_current_stock_status['csrfHash'] = $CSRFToken;
                 
                return response()->json($check_current_stock_status);
      }

    if (!Cart::add_to_cart($request)) {
       
        
                $settings = Fun::get_settings('system_settings', true);
              $response = Cart::get_cart_total($request->user_id);
              
                $this->response['error'] = false;
                $this->response['message'] = 'Cart Updated !';
               
                $this->response['data'] = [
                    'total_quantity' => ($request->qty== 0) ? '0' : strval($request->qty),
                    'sub_total' => strval($response['sub_total']),
                    'total_items' => (isset($response[0]->total_items)) ? strval($response[0]->total_items) : "0",
                    'tax_percentage' => (isset($response['tax_percentage'])) ? strval($response['tax_percentage']) : "0",
                    'tax_amount' => (isset($response['tax_amount'])) ? strval($response['tax_amount']) : "0",
                    'cart_count' => (isset($response[0]->cart_count)) ? strval($response[0]->cart_count) : "0",
                    'max_items_cart' => $settings['max_items_cart'],
                    'overall_amount' => round($response['overall_amount'],2),
                ];
                return response()->json($this->response);
            }
        }


    }   
    public function removeFromCart(Request $request)
    {
        $settings = Fun::get_settings('system_settings', true);
       
        $validator = Validator::make($request->all(), [
            'user_id'=>'required|integer',    
            'product_variant_id'=>'required',
          ]);
    
        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] = $validator->errors()->first();
            $this->response['data'] = array();
            print_r(json_encode($this->response));
            return;
        } else {
            //Fetching cart items to check wheather cart is empty or not
            $cart_total_response = Cart::get_cart_total($request->user_id);
            //$settings = get_settings('system_settings', true);
            if (!isset($cart_total_response[0]->total_items)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Cart Is Already Empty !';
                $this->response['data'] = array();
                return response()->json($this->response);
            }

            Cart::remove_from_cart($request);

            //Fetching cart items to send the details to api after the item is removed
            $cart_total_response = Cart::get_cart_total($request->user_id);
            $this->response['error'] = false;
            $this->response['message'] = 'Removed From Cart !';
            if (!empty($cart_total_response) && isset($cart_total_response)) {
                $this->response['data'] = [
                    'total_quantity' => strval($cart_total_response['quantity']),
                    'sub_total' => strval(round($cart_total_response['sub_total'],2)),
                    'total_items' => (isset($cart_total_response[0]->total_items)) ? strval($cart_total_response[0]->total_items) : "0",
                    'max_items_cart' => $settings['max_items_cart']
                ];
            } else {
                $this->response['data'] = [];
            }

            return response()->json($this->response);
        }
    }
    public function _getCart(Request $request)
    {
      
        $validator = Validator::make($request->all(), [
            'user_id'=>'required|integer',    
            'is_saved_for_later'=>'required',
          ]);
    
        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] = $validator->errors()->first();
            $this->response['data'] = array();
            return response()->json($this->response);
        } else {
            $user_id=$request->user_id;
            $is_saved_for_later=$request->is_saved_for_later;
            $is_saved_for_later = (isset($request->is_saved_for_later) && $request->is_saved_for_later== 1) ? $request->is_saved_for_later: 0;
            $cart_user_data = Cart::get_user_cart($request->user_id, $is_saved_for_later);
            
            $cart_total_response = Cart::get_cart_total($user_id,'', $is_saved_for_later);
            $tmp_cart_user_data = $cart_user_data;
        
            if (!empty($tmp_cart_user_data)) {
                for ($i = 0; $i < count($tmp_cart_user_data); $i++) {
                    $product_data =Fun::fetch_details(['id'=>$tmp_cart_user_data[$i]['product_variant_id']],'ec_products','id,status');
                     
                    if (!empty($product_data[0]->id)) {
                        $pro_details = Ec_product::fetch_product_json_data($user_id, null, $tmp_cart_user_data[$i]['id']);
                     
                        if (!empty($pro_details['product'])) {
                            if (trim($pro_details['product'][0]['availability']) == 0 && $pro_details['product'][0]['availability'] != null) {
                                Fun::update_details(['is_saved_for_later' => '1'],['product_variant_id'=>$cart_user_data[$i]['product_variant_id']], 'cart');
                                unset($cart_user_data[$i]);
                            }
                            if (!empty($pro_details['product'])) {
                                $cart_user_data[$i]['product_details']= $pro_details['product'];
                            } else {
                             
                                Fun::delete_details(['id'=>$cart_user_data[$i]['product_variant_id']], 'cart');
                                unset($cart_user_data[$i]);
                                continue;
                            }
                        } else {
                            Fun::delete_details(['id'=>$cart_user_data[$i]['product_variant_id']], 'cart');
                            unset($cart_user_data[$i]);
                            continue;
                        }
                    } else {
                        Fun::delete_details(['id'=>$cart_user_data[$i]['product_variant_id']], 'cart');
                        unset($cart_user_data[$i]);
                        continue;
                    }
                }
            }

            if (!empty($res)) {
                $this->response['error'] = true;
                $this->response['message'] = 'Cart Is Empty !';
                $this->response['data'] = array();
               
               return response()->json($this->response);
            }

           // $pro_details = Ec_product::get_products_By_ids($query,$request->user_id);
            
            $this->response['error'] = false;
            $this->response['message'] = 'Data Retrieved From Cart !';
            $this->response['total_quantity'] = $cart_total_response['quantity'];
            $this->response['sub_total'] = $cart_total_response['sub_total'];
            $this->response['delivery_charge'] ="0";
            $this->response['tax_percentage'] = (isset($cart_total_response['tax_percentage'])) ? $cart_total_response['tax_percentage'] : "0";
            $this->response['tax_amount'] = (isset($cart_total_response['tax_amount'])) ? round($cart_total_response['tax_amount'],2) : "0";
            $this->response['overall_amount'] = round($cart_total_response['overall_amount'],2);
            $this->response['total_arr'] = round($cart_total_response['total_arr'],2);
            $this->response['variant_id'] =  $cart_total_response['variant_id'];
            $this->response['data'] =array_values($cart_user_data);
            return response()->json($this->response);
        }
    }


    public function validatePromo(Request $request)
    {
        /*
            promo_code:'NEWOFF10'
            user_id:28
            final_total:'300'
        */
        $validator = Validator::make($request->all(), [
        'promo_code'=>'required',    
        'user_id'=>'required',
        'final_total'=>'required'
         ]);

        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] = $validator->errors()->first();
            $this->response['data'] = array();
            return response()->json($this->response);

        } else {
            print_r(json_encode(Cart::validate_promo_code($request->promo_code, $request->user_id, $request->final_total)));
        }
    }
}
