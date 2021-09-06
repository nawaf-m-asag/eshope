<?php
namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Fun;
use App\Models\Cart;
use App\Models\Ec_product;
use App\Models\Address;

class Order extends Model
{
    protected $table = 'ec_orders';
    protected $fillable = [
        'user_id',       
        'amount',
        'tax_amount',  
        'sub_total',
        'currency_id',    
        'shipping_amount',
        'coupon_code',    
        'discount_amount',
        'payment_id',    
        'description',
   
];
    public static function place_order($data)
    {
        
            $response = array();
            $user = Fun::fetch_details(['id'=>$data['user_id']],'ec_customers');

            $product_variant_id = explode(',', $data['product_variant_id']);
            $quantity = explode(',', $data['quantity']);
            $otp = rand(100000, 999999);
            
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
            
                $system_settings = Fun::get_settings('system_settings', true);
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
                    $wallet_balance = Fun::update_wallet_balance('debit', $data['user_id'], $data['wallet_balance_used'], "Used against Order Placement");
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

                // object ec_order by user id
                $status = (isset($data['active_status'])) ? $data['active_status'] : 'received';
                $currency= Fun::fetch_details(['is_default'=>1],'ec_currencies','id');
                $order_data = [
                    'user_id' => $data['user_id'],
                    'amount' => $final_total,
                    'tax_amount' => $tax_amount_total,
                    'sub_total'=>$total,
                    'currency_id'=>isset($currency->id)?$currency->id:'0',
                    'shipping_amount' => $delivery_charge,
                    'coupon_code' =>(isset($data['promo_code'])) ? $data['promo_code'] :'',
                    'discount_amount' =>(isset($promo_code_discount) && $promo_code_discount != NULL) ? $promo_code_discount : '0',
                    'payment_id' => '1',
                    'description'=>''
                ];
               
                

                // if (isset($data['address_id']) && !empty($data['address_id'])) {
                //     $order_data['address'] = $data['address_id'];
                // }
                $address_data = Address::get_address(null, $data['address_id'], false);
 
                if (isset($data['delivery_date']) && !empty($data['delivery_date']) && !empty($data['delivery_time']) && isset($data['delivery_time'])) {
                    $order_data['description'].="delivery date:".date('Y-m-d', strtotime($data['delivery_date']));
                    $order_data['description'].="delivery time:".$data['delivery_time'];

                    $order_data['description'] .="latitude:". isset($address_data[0]['latitude'])?$address_data[0]['latitude']:'';
                    $order_data['description'] .="longitude:". isset($address_data[0]['longitude'])?$address_data[0]['longitude']:'';
                }
                $last_order_id=Order::create( $order_data);
                
                // object ec_order_addresses by order id
                if (!empty($address_data)) {
                    $order_address['name'] = $address_data[0]['name'];
                    $order_address['phone'] = $address_data[0]['mobile'];
                    $order_address['email'] ='';
                    $order_address['country'] = $address_data[0]['country_code'];
                    $order_address['state'] = $address_data[0]['state'];
                    $order_address['city'] = $address_data[0]['city'];
                    $order_address['order_id'] =$last_order_id->id;
                    $order_address['address'] = (!empty($address_data[0]['address'])) ? $address_data[0]['address'] . ', ' : '';
                    $order_address['address'] .= (!empty($address_data[0]['landmark'])) ? $address_data[0]['landmark'] . ', ' : '';
                    $order_address['address'] .= (!empty($address_data[0]['area'])) ? $address_data[0]['area'] . ', ' : '';
                    $order_address['address'] .= (!empty($address_data[0]['city'])) ? $address_data[0]['city'] . ', ' : '';
                    $order_address['address'] .= (!empty($address_data[0]['state'])) ? $address_data[0]['state'] . ', ' : '';
                    $order_address['address'] .= (!empty($address_data[0]['country'])) ? $address_data[0]['country'] . ', ' : '';
                    $order_address['address'] .= (!empty($address_data[0]['pincode'])) ? $address_data[0]['pincode'] : '';
                }
                // if (!empty($_POST['latitude']) && !empty($_POST['longitude'])) {
                //     $order_data['latitude'] = $_POST['latitude'];
                //     $order_data['longitude'] = $_POST['longitude'];
                // }
                DB::table('ec_order_addresses')->insert($order_address);
        
                for ($i = 0; $i < count($product_variant); $i++) {
                    $product_variant_data[$i] = [
                        'order_id' => $last_order_id->id,
                        'product_name' =>$product_variant[$i]->name,
                        'product_id' => $product_variant[$i]->id,
                        'qty' => $quantity[$i],
                        'price' => $pv_price[$i],
                        'tax_amount' =>$pv_price[$i]*$tax_percentage[$i]/100,
                    ];
                   
               
              DB::table('ec_order_product')->insert($product_variant_data[$i]);
              
              $product_variant_data_json[$i] = [
                'user_id' => $data['user_id'],
                'order_id' => $last_order_id->id,
                'product_name' => $product_variant[$i]->name,
                'variant_name' => $product_variant[$i]->name,
                'product_variant_id' => strval($product_variant[$i]->id),
                'quantity' => $quantity[$i],
                'price' => $pv_price[$i],
                'tax_percent' =>strval($tax_percentage[$i]),
                'tax_amount' => 0,
                'sub_total' => $subtotal[$i],
                'status' =>  json_encode(array(array($status, date("d-m-Y h:i:sa")))),
                'active_status' => $status,
            ];
        
                }
                $product_variant_ids = explode(',', $data['product_variant_id']);

                $qtns = explode(',', $data['quantity']);
                //update_stock($product_variant_ids, $qtns);

                $overall_total = array(
                    'total_amount' => array_sum($subtotal),
                    'delivery_charge' => $delivery_charge,
                    'tax_amount' => array_sum($tax_amount),
                    'tax_percentage' => array_sum($tax_percentage),
                    'discount' =>  $order_data['coupon_code'],
                    'wallet' =>   '0',
                    'final_total' =>  $final_total,
                    'total_payable' =>  $total_payable,
                    'otp' => $otp,
                    'address' => ($data['address_id']) ? $data['address_id']: '',
                    'payment_method' => $data['payment_method']
                );
                if (trim(strtolower($data['payment_method'])) != 'paypal' || trim(strtolower($data['payment_method'])) != 'stripe') {
                    $overall_order_data = array(
                        'cart_data' => $cart_data,
                        'order_data' => $overall_total,
                        'subject' => 'Order received successfully',
                        'user_data' => $user[0],
                        'system_settings' => $system_settings,
                        'user_msg' => 'Hello, Dear ' .ucfirst($user[0]->name) . ', We have received your order successfully. Your order summaries are as followed',
                        'otp_msg' => 'Here is your OTP. Please, give it to delivery boy only while getting your order.',
                    );
                    // if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1) {
                    //     $system_settings = get_settings('system_settings', true);
                    //     if (isset($system_settings['support_email']) && !empty($system_settings['support_email'])) {
                    //         send_mail($system_settings['support_email'], 'New order placed ID #' . $last_order_id, 'New order received for ' . $system_settings['app_name'] . ' please process it.');
                    //     }
                    // }

                    //send_mail($user[0]['email'], 'Order received successfully', $this->load->view('admin/pages/view/email-template.php', $overall_order_data, TRUE));
                }
                Cart::remove_from_cart($data);
                $user_balance = Fun::fetch_details(['id' => $data['user_id']], 'ec_customers', 'balance');

                $response['error'] = false;
                $response['message'] = 'Order Placed Successfully';
                $response['order_id'] = $last_order_id->id;
                $response['order_item_data'] = $product_variant_data_json;
                $response['balance'] = $user_balance;
                return $response;
            }
            else {
            $user_balance = Fun::fetch_details(['id' => $data['user_id']], 'ec_customers', 'balance');
            $response['error'] = true;
            $response['message'] = "Product(s) Not Found!";
            $response['balance'] = $user_balance;
            return $response;
        }
    }
}
