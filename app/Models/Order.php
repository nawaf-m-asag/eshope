<?php
namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Fun;
use App\Models\Cart;
use App\Models\Ec_product;
use App\Models\Address;
use Illuminate\Support\Facades\Route;
use RvMedia;

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
        'address_id',
        'otp',
        'delivery_date',
        'delivery_time',
        


   
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
                    'payment_id' => '3',
                    'description'=>'',
                    'address_id'=>$data['address_id'],
                    'delivery_date'=>date('Y-m-d', strtotime($data['delivery_date'])),
                    'delivery_time'=>$data['delivery_time']

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
                if ($system_settings['is_delivery_boy_otp_setting_on'] == '1') {
                    $order_data['otp'] = $otp;
                } else {
                    $order_data['otp'] = 0;
                }
                $last_order_id=Order::create( $order_data);
                $orderId=strval(10000000+$last_order_id->id);
                DB::table('ec_order_histories')->insert(['action'=>'create_order','description'=>'#'.$orderId.'  '. $address_data[0]['name'].' طلب جديد بواسطة التطبيق من ','user_id'=> $data['user_id'],'order_id'=>$last_order_id->id,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
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
                    $order_address['address'] .= (!empty($address_data[0]['city'])) ? $address_data[0]['city'] : '';
                   
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










    public static function fetch_orders($order_id = NULL, $user_id = NULL, $status = NULL, $delivery_boy_id = NULL, $limit = NULL, $offset = NULL, $sort = NULL, $order = NULL, $download_invoice = false, $start_date = null, $end_date = null, $search = null)
    {

      
        $where = [];
        
        $count_res = DB::table('ec_orders as o')
        ->leftJoin('ec_customers as ec', 'ec.id','=','o.user_id')
        ->leftJoin('ec_order_product as op','o.id','=','op.order_id')
        ->leftJoin('ec_products as p','op.product_id','=','p.id');
        if (isset($order_id) && $order_id != null) {
            $where['o.id'] = $order_id;
        }

        // if (isset($delivery_boy_id) && $delivery_boy_id != null) {
        //     $where['o.delivery_boy_id'] = $delivery_boy_id;
        // }

        if (isset($user_id) && $user_id != null) {
            $where['o.user_id'] = $user_id;
        }

        // if (isset($start_date) && $start_date != null && isset($end_date) && $end_date != null) {
        //     $count_res->where(" DATE(o.date_added) >= DATE('" . $start_date . "') ");
        //     $count_res->where(" DATE(o.date_added) <= DATE('" . $end_date . "') ");
        // }

        // if (isset($search) and $search != null) {

        //     $filters = [
        //         'ec.name' => $search,
        //         'ec.email' => $search,
        //         'o.id' => $search,
        //         'o.phone' => $search,
        //         'o.address' => $search,
        //         'o.payment_method' => $search,
        //         'o.delivery_time' => $search,
        //         'o.status' => $search,
        //         'o.active_status' => $search,
        //         'o.date_added' => $search,
        //         'p.name' => $search
        //     ];
        // }
        // if (isset($filters) && !empty($filters)) {
        //     $count_res->group_Start();
        //     $count_res->or_like($filters);
        //     $count_res->group_End();
        // }

        $count_res->where($where);
        // if (isset($status) &&  is_array($status) &&  count($status) > 0) {
        //     $status = array_map('trim', $status);
        //     $count_res->where_in('o.active_status', $status);
        // }
        // if ($sort == 'date_added') {
        //     $sort = 'o.date_added';
        // }
        $count_res->orderBy($sort, $order);
        $total=0;
        $total = $count_res ->distinct('o.id')->count();
    
        $search_res = DB::table('ec_orders as o')
        ->selectRaw('o.id,o.user_id,o.address_id,o.sub_total as total,o.shipping_amount as delivery_charge,o.coupon_code as promo_code,o.discount_amount as promo_discount,o.discount_amount as discount,o.amount as total_payable,o.amount as final_total,o.delivery_time,o.delivery_date,o.otp,o.tax_amount as total_tax_amount,o.status,o.updated_at,o.created_at as date_added,ec.name as username,addr.latitude,addr.longitude,ec_addr.address,ec_addr.phone as mobile,ec.country_code,p.name')
        ->leftJoin('ec_customers as ec','ec.id','=','o.user_id')
        ->leftJoin('addresses as addr','addr.id','=','o.address_id')
        ->leftJoin('ec_order_addresses as ec_addr','ec_addr.order_id','=','o.id')
        
        ->leftJoin('ec_order_product as op','o.id','=' ,'op.order_id')
        ->leftJoin('ec_products as p','op.product_id','=','p.id');
        $search_res->where($where);
        // if (isset($start_date) && $start_date != null && isset($end_date) && $end_date != null) {
        //     $search_res->where(" DATE(o.date_added) >= DATE('" . $start_date . "') ");
        //     $search_res->where(" DATE(o.date_added) <= DATE('" . $end_date . "') ");
        // }
        // if (isset($status) &&  is_array($status) &&  count($status) > 0) {
        //     $status = array_map('trim', $status);
        //     $search_res->whereIn('o.active_status', $status);
        // }
        // if (isset($filters) && !empty($filters)) {
        //     $search_res->group_Start();
        //     $search_res->or_like($filters);
        //     $search_res->group_End();
        // }
        if (empty($sort)) {
            $sort = `o.created_at`;
        }
      //  $search_res->groupBy('o.id');
        $search_res->orderBy($sort, $order);
        if ($limit != null || $offset != null) {
            $search_res->limit($limit)->offset($offset);
        }

        $order_details = $search_res->get()->toArray();

        for ($i = 0; $i < count($order_details); $i++) {
            $order_item_data= DB::table('ec_order_product as op')
            ->selectRaw('op.id,op.order_id,op.product_name as name,p.name as product_name,op.product_name as variant_name,op.qty as quantity,op.price,op.tax_amount,p.id as product_variant_id,pv.configurable_product_id as product_id,p.images as image,(Select count(id) from ec_order_product where order_id = op.order_id ) as order_counter')
            ->leftJoin('ec_products as p','op.product_id','=','p.id')
            ->leftJoin('ec_product_variations as pv','pv.product_id','=','p.id')
            ->where('op.order_id', $order_details[$i]->id);
            $order_item_data = $order_item_data->get()->toArray();
           
           // $return_request = fetch_details(['user_id' => $user_id], 'return_requests');
            $order_details[$i]->delivery_boy_id=null;
            $status= $order_details[$i]->status;
            if($status=='pending')
            $status='received';
            if($status=='processing')
            $status='processed';
            if($status=='delivering')
            $status='shipped';
            if($status=='delivered')
            $status='delivered';
            if($status=='completed')
            $status='delivered';
            if($status=='canceled')
            $status='cancelled';
    
            $order_details[$i]->status=[$status,date('d-m-Y h:i:sa', strtotime($order_details[$i]->updated_at))];
            $order_details[$i]->active_status=$status;
            //static
            $order_details[$i]->is_delivery_charge_returnable='0';
            $order_details[$i]->wallet_balance='0';

            unset($order_details[$i]->updated_at);
            $returnable_count = 0;
            $cancelable_count = 0;
            $already_returned_count = 0;
            $already_cancelled_count = 0;
            $return_request_submitted_count = 0;
            $total_tax_percent = $total_tax_amount = 0;

            for ($k = 0; $k < count($order_item_data); $k++) {
                if (!empty($order_item_data)) {
                    $order_item_data[$k]->user_id=strval($order_details[$i]->user_id);
                    $order_item_data[$k]->variant_name=strval($order_details[$i]->name);
                    $order_item_data[$k]->product_id=($order_item_data[$k]->product_id==null)?$order_item_data[$k]->product_variant_id:$order_item_data[$k]->product_id;
                    $varaint_data = Ec_product::getVariant_ids($order_item_data[$k]->product_variant_id);
                 
                    $order_item_data[$k]->varaint_ids = ($varaint_data['variant_ids']!=null) ? $varaint_data['variant_ids'] : '';
                    $order_item_data[$k]->variant_values = (!empty($varaint_data)) ? $varaint_data['variant_values'] : '';
                    $order_item_data[$k]->attr_name= ($varaint_data['attr_name']!=null) ? $varaint_data['attr_name'] : '';
                    $order_item_data[$k]->variant_values = (!empty($order_item_data[$k]->variant_values)) ? $order_item_data[$k]->variant_values: $order_item_data[$k]->name;
                    $order_item_data[$k]->name= (!empty($order_item_data[$k]->name)) ? $order_item_data[$k]->name: $order_item_data[$k]->product_name;
                    $order_item_data[$k]->status=  $order_details[$i]->status;
                    $order_item_data[$k]->active_status=$status;
                    $order_item_data[$k]->date_added=$order_details[$i]->date_added;
                    $order_item_data[$k]->discounted_price=null;
                    $order_item_data[$k]->discount="0";
                    $order_item_data[$k]->sub_total=strval($order_item_data[$k]->price*$order_item_data[$k]->quantity);
                    $order_item_data[$k]->deliver_by=null;
                    $order_item_data[$k]->order_cancel_counter="0";
                    $order_item_data[$k]->order_return_counter="0";
                    $sales=Ec_product::getSalesCount($order_item_data[$k]->product_id);
                    $attributes=Ec_product::getProAttributes($order_item_data[$k]->product_id);
                    $order_item_data[$k]->type=(!empty($attributes)&& $sales>1)?"variable_product":"simple_product";
                    $order_item_data[$k]->tax_percent= ($order_item_data[$k]->tax_amount!=0) ?strval(round($order_item_data[$k]->price/$order_item_data[$k]->tax_amount)):'0';
                    if (!in_array($order_item_data[$k]->active_status, ['returned', 'cancelled'])) {
                        $total_tax_percent = $total_tax_percent + ($order_item_data[$k]->tax_amount!=0) ?round($order_item_data[$k]->price/$order_item_data[$k]->tax_amount):'0';
                        $total_tax_amount  = $total_tax_amount + $order_item_data[$k]->tax_amount;
                    }

                    // for ($j = 0; $j < count($order_item_data[$k]['status']); $j++) {
                    //     $order_item_data[$k]['status'][$j][1] = date('d-m-Y h:i:sa', strtotime($order_item_data[$k]['status'][$j][1]));
                    // }
                    $product_images=json_decode($order_item_data[$k]->image);
                    $default_imag=null;
                    if(!empty($product_images))
                    $default_imag=$product_images[0];

                    $order_item_data[$k]->image_sm = RvMedia::getImageUrl($default_imag,'small', false, RvMedia::getDefaultImage());
                    $order_item_data[$k]->image_md= RvMedia::getImageUrl($default_imag,'medium', false, RvMedia::getDefaultImage());
                    $order_item_data[$k]->image= RvMedia::getImageUrl($default_imag,null, false, RvMedia::getDefaultImage());
                    $order_item_data[$k]->is_already_returned=  ($order_item_data[$k]->active_status == 'returned') ? '1' : '0';
                    $order_item_data[$k]->is_already_cancelled= ($order_item_data[$k]->active_status== 'cancelled') ? '1' : '0';
                    //$return_request_key = array_search($order_item_data[$k]->id, array_column($return_request, 'order_item_id'));
                    // if ($return_request_key !== false) {
                    //     $order_item_data[$k]['return_request_submitted'] = $return_request[$return_request_key]['status'];
                    //     if ($order_item_data[$k]['return_request_submitted'] == '1') {
                    //         $return_request_submitted_count += $order_item_data[$k]['return_request_submitted'];
                    //     }
                    // } else {
                        // $order_item_data[$k]['return_request_submitted'] = null;
                        // $return_request_submitted_count = null;
                    // }
                    $order_item_data[$k]->is_returnable='0';
                    $order_item_data[$k]->is_cancelable='0';
                    $order_item_data[$k]->return_request_submitted= null;
                    $return_request_submitted_count = null;
                    $returnable_count += $order_item_data[$k]->is_returnable;
                    $cancelable_count += $order_item_data[$k]->is_cancelable;
                    $already_returned_count += $order_item_data[$k]->is_already_returned;
                    $already_cancelled_count += $order_item_data[$k]->is_already_cancelled;
                }
            }
            $order_details[$i]->payment_method="COD";
            $order_details[$i]->is_returnable= ($returnable_count >= 1) ? '1' : '0';
            $order_details[$i]->is_cancelable= ($cancelable_count >= 1) ? '1' : '0';
            $order_details[$i]->is_already_returned= ($already_returned_count == count($order_item_data)) ? '1' : '0';
            $order_details[$i]->is_already_cancelled = ($already_cancelled_count == count($order_item_data)) ? '1' : '0';
            if ($return_request_submitted_count == null) {
                $order_details[$i]->return_request_submitted= null;
            } else {
                $order_details[$i]->return_request_submitted= ($return_request_submitted_count == count($order_item_data)) ? '1' : '0';
            }
            $order_details[$i]->total= strval($order_details[$i]->total- $total_tax_amount);
            //$order_details[$i]->address= Fun::output_escaping($order_details[$i]->address);
            $order_details[$i]->username= Fun::output_escaping($order_details[$i]->username);
            $order_details[$i]->total_tax_percent= strval($total_tax_percent);
            $order_details[$i]->total_tax_amount = strval($total_tax_amount);
            if ($download_invoice == true or $download_invoice == 1) {
                
                    dd( redirect('/generate-invoice'));
                
            
            }
       
            if (!empty($order_item_data)) {
                $order_details[$i]->order_items= $order_item_data;
            } else {
                unset($order_details[$i]);
            }
        }

        $order_data['total']= strval($total);
        $order_data['order_data']= array_values($order_details);
        return $order_data;
    }
}
