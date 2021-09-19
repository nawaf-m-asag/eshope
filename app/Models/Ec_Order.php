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
use OrderHelper;
use Botble\Ecommerce\Models\Order;
class Ec_Order extends Model
{

    /**
     * @var OrderInterface
     */
    protected $orderRepository;

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
                
                $res = (isset($data['add_id'])) ?DB::table('ec_shipping_rules')->select('price')->where('id',$data['add_id'])->where('type','base_on_price')->get() : 0;
                $delivery_charge =isset($res[0]->price)?$res[0]->price:0;
               // $delivery_charge = (isset($data['delivery_charge'])) ? $data['delivery_charge'] : 0;
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
                $currency= Fun::fetch_details(['is_default'=>1],'ec_currencies','id,title');
               
                $order_data = [
                    'user_id' => $data['user_id'],
                    'amount' => $final_total,
                    'tax_amount' => $tax_amount_total,
                    'sub_total'=>$total,
                    'currency_id'=>isset($currency[0]->id)?$currency[0]->id:'0',
                    'shipping_amount' => $delivery_charge,
                    'coupon_code' =>(isset($data['promo_code'])) ? $data['promo_code'] :'',
                    'discount_amount' =>(isset($promo_code_discount) && $promo_code_discount != NULL) ? $promo_code_discount : '0',
                    'description'=>'',
                    'address_id'=>$data['address_id'],
                    'delivery_date'=>date('Y-m-d', strtotime($data['delivery_date'])),
                    'delivery_time'=>$data['delivery_time']

                ];
           
                $address_data = Address::get_address(null, $data['address_id'], false);
 
                if (isset($data['delivery_date']) && !empty($data['delivery_date']) && !empty($data['delivery_time']) && isset($data['delivery_time'])) {
                    $order_data['description'].=" delivery date:".date('Y-m-d', strtotime($data['delivery_date']));
                    $order_data['description'].=" delivery time:".$data['delivery_time'];
                    $order_data['description'] .=isset($data['remittance_no'])?" رقم الحوالة:".$data['remittance_no']:'';
                }
                if ($system_settings['is_delivery_boy_otp_setting_on'] == '1') {
                    $order_data['otp'] = $otp;
                } else {
                    $order_data['otp'] = 0;
                }
                $last_order_id=Ec_Order::create( $order_data);
               

            $payment_id=DB::table('payments')->insertGetId(
                    [
                        'currency'=>isset($currency[0]->title)?$currency[0]->title:null,
                        'customer_id'=>$data['user_id'],
                        'charge_id'=> strtoupper(substr(md5(microtime()),rand(0,26),10)),
                        'payment_channel'=>($data['payment_method']=='Remittance')?'bank_transfer':strtolower($data['payment_method']),
                        'amount'=>$final_total,
                        'order_id'=> $last_order_id->id,
                        'customer_type'=>'Botble\Ecommerce\Models\Customer',
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),

            ]);

            DB::table('ec_orders')->where('id',$last_order_id->id)->update(['payment_id'=>$payment_id]);
                $orderId=strval(10000000+$last_order_id->id);
                DB::table('ec_order_histories')->insert(['action'=>'create_order','description'=>'#'.$orderId.'  طلب جديد بواسطة التطبيق من '. $address_data[0]['name'],'user_id'=> $data['user_id'],'order_id'=>$last_order_id->id,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
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
                    $order_address['address'] .= (!empty($address_data[0]['city'])) ? $address_data[0]['city']: '';
                    // $order_address['address'] .= (!empty($address_data[0]['latitude'])) ? 'lat='.$address_data[0]['latitude'] . '&' : '';
                    // $order_address['address'] .= (!empty($address_data[0]['longitude'])) ? 'lng='.$address_data[0]['longitude'] : '';

                   
                }
               
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
                    'wallet' =>'0',
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
        if (isset($order_id) && $order_id != null) {
            $where['o.id'] = $order_id;
        }

        if (isset($user_id) && $user_id != null) {
            $where['o.user_id'] = $user_id;
        }

        if ($sort == 'date_added') {
            $sort = 'o.created_at';
        }
        $search_res = DB::table('ec_orders as o')
        ->selectRaw('o.id,o.user_id,o.address_id,o.sub_total as total,o.shipping_amount as delivery_charge,o.coupon_code as promo_code,o.discount_amount as promo_discount,o.discount_amount as discount,o.amount as final_total,o.delivery_time,o.delivery_date,o.otp,o.tax_amount as total_tax_amount,o.status,o.updated_at,o.created_at as date_added,ec.name as username,addr.latitude,addr.longitude,ec_addr.address,ec_addr.phone as mobile,ec.country_code')
        ->leftJoin('ec_customers as ec','ec.id','=','o.user_id')
        ->leftJoin('addresses as addr','addr.id','=','o.address_id')
        ->leftJoin('ec_order_addresses as ec_addr','ec_addr.order_id','=','o.id');

        $search_res->where($where);
        if (isset($search) && !empty($search)) {
            $search_res->join('ec_order_product as op',function($search_res) use($search){
            $search_res->on('op.order_id','=','o.id');
            $search_res->where('op.product_name','like','%'.trim($search).'%')->limit(1);
        });
            
        }
        if (empty($sort)) {
            $sort = `o.created_at`;
        }

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
    
            $order_details[$i]->status=Ec_Order::getOrderStatus($order_details[$i]->id);
            $order_details[$i]->active_status=$status;
            //static
            $order_details[$i]->is_delivery_charge_returnable='0';
            $order_details[$i]->wallet_balance='0';
            $order_details[$i]->total_payable=Ec_Order::get_total_payable($order_details[$i]->id);
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
                    $order_item_data[$k]->variant_name=strval($order_item_data[$k]->name);
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
                        
                        $total_tax_percent = $total_tax_percent + Cart::get_tax_percentage($order_item_data[$k]->product_variant_id);
                        $total_tax_amount  = $total_tax_amount + $order_item_data[$k]->tax_amount;
                    }

                    $product_images=json_decode($order_item_data[$k]->image);
                    $default_imag=null;
                    if(!empty($product_images)){
                        foreach ($product_images as $key => $value) {
                            if($value!=null){
                                $default_imag=$product_images[$key];
                                break;
                            }
                            
                        }
                    }
                    

                    $order_item_data[$k]->image_sm = RvMedia::getImageUrl($default_imag,'small', false, RvMedia::getDefaultImage());
                    $order_item_data[$k]->image_md= RvMedia::getImageUrl($default_imag,'medium', false, RvMedia::getDefaultImage());
                    $order_item_data[$k]->image= RvMedia::getImageUrl($default_imag,null, false, RvMedia::getDefaultImage());
                    $order_item_data[$k]->is_already_returned=  ($order_item_data[$k]->active_status == 'returned') ? '1' : '0';
                    $order_item_data[$k]->is_already_cancelled= ($order_item_data[$k]->active_status== 'cancelled') ? '1' : '0';
                   
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
            $order_details[$i]->payment_method="cod";
            $order_details[$i]->is_returnable= ($returnable_count >= 1) ? '1' : '0';
            $order_details[$i]->is_cancelable= ( $order_details[$i]->active_status=='received'|| $order_details[$i]->active_status=='processed') ? '1' : '0';
            $order_details[$i]->is_already_returned= ($already_returned_count == count($order_item_data)) ? '1' : '0';
            $order_details[$i]->is_already_cancelled =( $order_details[$i]->active_status=='canceled') ? '1' : '0';
            if ($return_request_submitted_count == null) {
                $order_details[$i]->return_request_submitted= null;
            } else {
                $order_details[$i]->return_request_submitted= ($return_request_submitted_count == count($order_item_data)) ? '1' : '0';
            }
            $order_details[$i]->total= strval($order_details[$i]->total- $total_tax_amount);
            $order_details[$i]->username= Fun::output_escaping($order_details[$i]->username);
            $order_details[$i]->total_tax_percent= strval($total_tax_percent);
            $order_details[$i]->total_tax_amount = strval($total_tax_amount);
            if ($download_invoice == true or $download_invoice == 1) {
                //use order Model for web 
                $order = Order::findOrFail($order_details[$i]->id);
                //lod invoices template 
                $invoice = strval(view('plugins/ecommerce::invoices.template', compact('order')));
                $order_details[$i]->invoice_html=$invoice;
               
            }

            if (!empty($order_item_data)) {
                $order_details[$i]->order_items= $order_item_data;
            } else {
                unset($order_details[$i]);
            }
        }

        $order_data['total']= strval($i);
        $order_data['order_data']= array_values($order_details);
        return $order_data;
    }
    public static function  getOrderStatus($id){
       $query= DB::table('ec_order_histories')
        ->select('action','created_at')
        ->where('order_id',$id)->get();

      
        $status=[];
        foreach ($query as $key => $value) {
           if($value->action=='create_order')
           $status[]=["received", $value->created_at];
           if($value->action=='confirm_order')
           $status[]=["processed", $value->created_at];
           if($value->action=='create_shipment')
           $status[]=["shipped", $value->created_at];
           $update_status= DB::table('ec_orders')->select('status')->where('id',$id)->get();
           if($value->action=='update_status'){
            if(isset($update_status[0]->status)&& $update_status[0]->status=='completed')
            $status[]=["delivered", $value->created_at];
      
           }
           if(isset($update_status[0]->status)&& $update_status[0]->status=='canceled')
           $status[]=["cancelled", $value->created_at];

        }
        return $status;
    }
    public static function get_total_payable($order_id){
        $amount=  Fun::fetch_details(['order_id'=>$order_id,'status'=>'completed'],'payments','amount');
        if(isset($amount[0]->amount)){
            return strval($amount[0]->amount);
        }
        else{
            return "0";
        }
    }
}