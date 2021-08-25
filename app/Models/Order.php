<?php
namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Fun;
use App\Models\Cart;
use App\Models\Ec_product;

class Order extends Model
{
    public static function place_order($data)
    {
        // $data = escape_array($data);
        // $CI = &get_instance();
        // $CI->load->model('Address_model');

        $response = array();
        $user = Fun::fetch_details('id' ,$data['user_id'],'ec_customers');

        $product_variant_id = explode(',', $data['product_variant_id']);
        $quantity = explode(',', $data['quantity']);
        $otp = mt_rand(100000, 999999);
        
        $check_current_stock_status = Cart::validate_stock($product_variant_id, $quantity);

        if (isset($check_current_stock_status['error']) && $check_current_stock_status['error'] == true) {
            return $check_current_stock_status;
        }
    
        /* Calculating Final Total */

        $total = 0;
        $product_variant = DB::table('ec_products as p')
             ->whereIn('p.id', $product_variant_id)
             ->select('p.*')->get()->toArray();


        
      
        
        if (!empty($product_variant)) {
           
           // $system_settings = get_settings('system_settings', true);
            $delivery_charge = (isset($data['delivery_charge'])) ? $data['delivery_charge'] : 0;
            $gross_total = 0;
            $tax_amount_total=0;
            $cart_data = [];
            
            for ($i = 0; $i < count($product_variant); $i++) {
                $product_variant[$i]->percentage=Cart::get_tax_percentage($product_variant[$i]->id);
                $pv_price[$i] = ($product_variant[$i]->sale_price> 0 &&$product_variant[$i]->sale_price != null) ? $product_variant[$i]->sale_price : $product_variant[$i]->price;
                $tax_percentage[$i] = (isset($product_variant[$i]->percentage) && intval($product_variant[$i]->percentage) > 0 && $product_variant[$i]->percentage != null) ? $product_variant[$i]->percentage: '0';
   
             
                $subtotal[$i] = ($pv_price[$i])  * $quantity[$i];
                $pro_name[$i] = $product_variant[$i]->name;
               
                $variant_info = Ec_product::getVariant_ids($product_variant[$i]->id);
                
                $product_variant[$i]->variant_name= (isset($variant_info['variant_values']) && !empty($variant_info['variant_values'])) ?$variant_info['variant_values']: "";
                
                $tax_percentage[$i] = (!empty($product_variant[$i]->percentage)) ? $product_variant[$i]->percentage: 0;
                if ($tax_percentage[$i] != NUll && $tax_percentage[$i] > 0) {
                    $tax_amount[$i] = round($subtotal[$i] *  $tax_percentage[$i] / 100, 2);
                } else {
                    $tax_amount[$i] = 0;
                    $tax_percentage[$i] = 0;
                }
                $tax_amount_total+= $tax_amount[$i];
                $gross_total += $subtotal[$i];
                $total += $subtotal[$i];
                $total = round($total, 2);
                $gross_total  = round($gross_total, 2);
                
                array_push($cart_data, array(
                    'name' => $pro_name[$i],
                    'tax_amount' => $tax_amount[$i],
                    'qty' => $quantity[$i],
                    'sub_total' => $subtotal[$i],
                ));
               
            }
           // $system_settings = get_settings('system_settings', true);

            /* Calculating Promo Discount */
            // if (isset($data['promo_code']) && !empty($data['promo_code'])) {

            //     $promo_code = validate_promo_code($data['promo_code'], $data['user_id'], $data['final_total']);

            //     if ($promo_code['error'] == false) {

            //         if ($promo_code['data'][0]['discount_type'] == 'percentage') {
            //             $promo_code_discount =  floatval($total  * $promo_code['data'][0]['discount'] / 100);
            //         } else {
            //             $promo_code_discount = $promo_code['data'][0]['discount'];
            //             // $promo_code_discount = floatval($total - $promo_code['data'][0]['discount']);
            //         }
            //         if ($promo_code_discount <= $promo_code['data'][0]['max_discount_amount']) {
            //             $total = floatval($total) - $promo_code_discount;
            //         } else {
            //             $total = floatval($total) - $promo_code['data'][0]['max_discount_amount'];
            //             $promo_code_discount = $promo_code['data'][0]['max_discount_amount'];
            //         }
            //     } else {
            //         return $promo_code;
            //     }
            // }

            $final_total = $total + $delivery_charge;
            $final_total = round($final_total, 2);

            /* Calculating Wallet Balance */
            $total_payable = $final_total;
            if ($data['is_wallet_used'] == '1' && $data['wallet_balance_used'] <= $final_total) {

                /* function update_wallet_balance($operation,$user_id,$amount,$message="Balance Debited") */
                $wallet_balance = update_wallet_balance('debit', $data['user_id'], $data['wallet_balance_used'], "Used against Order Placement");
                if ($wallet_balance['error'] == false) {
                    $total_payable -= $data['wallet_balance_used'];
                    $Wallet_used = true;
                } else {
                    $response['error'] = true;
                    $response['message'] = $wallet_balance['message'];
                    return $response;
                }
            } else {
                if ($data['is_wallet_used'] == 1) {
                    $response['error'] = true;
                    $response['message'] = 'Wallet Balance should not exceed the total amount';
                    return $response;
                }
            }

            $status = (isset($data['active_status'])) ? $data['active_status'] : 'received';
            $order_data = [
                'user_id' => $data['user_id'],
                'amount' => $final_total,
                'tax_amount' => $tax_amount_total,
                'sub_total'=>$total,
                'currency_id'=>3,
                'shipping_amount' => $delivery_charge,
                'coupon_code' =>(isset($data['promo_code'])) ? $data['promo_code'] :'',
                'discount_amount' =>(isset($promo_code_discount) && $promo_code_discount != NULL) ? $promo_code_discount : '0',
                'payment_id' => '1',
            ];
            DB::table('ec_orders')->insert( $order_data);

            dd( $order_data);
            if (isset($data['address_id']) && !empty($data['address_id'])) {
                $order_data['address_id'] = $data['address_id'];
            }

            if (isset($data['delivery_date']) && !empty($data['delivery_date']) && !empty($data['delivery_time']) && isset($data['delivery_time'])) {
                $order_data['delivery_date'] = date('Y-m-d', strtotime($data['delivery_date']));
                $order_data['delivery_time'] = $data['delivery_time'];
            }

         
        }}
}
