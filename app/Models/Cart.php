<?php
namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
   public static function get_cart_count($user_id)
    {
        $ci = DB::table('cart');
        if (!empty($user_id)) {
            $ci= $ci->where('user_id', $user_id);
        }
        $res= $ci->where('qty','!=', 0)
        ->where('is_saved_for_later', 0)
        ->distinct()->count();
        return $res;
    }
    public static function is_variant_available_in_cart($product_variant_id, $user_id)
    {
        $ci = DB::table('cart');
        $res= $ci->where('product_variant_id', $product_variant_id)
        ->where('user_id', $user_id)
        ->where('qty','!=', 0)
        ->where('is_saved_for_later', 0)
        ->select('id')
        ->get()->toarray();

        if (!empty($res[0]->id)) {
            return true;
        } else {
            return false;
        }
    }
    public static function add_to_cart($data)
    {
     
        $product_variant_id = explode(',', $data->product_variant_id);
        $qty = explode(',', $data->qty);

        $check_current_stock_status = Cart::validate_stock($product_variant_id, $qty);
       
        if (!empty($check_current_stock_status) && $check_current_stock_status['error'] == true) {
            // $check_current_stock_status['csrfName'] = $this->security->get_csrf_token_name();
            // $check_current_stock_status['csrfHash'] = $this->security->get_csrf_hash();
            // print_r(json_encode($check_current_stock_status));
            return true;
        }

        for ($i = 0; $i < count($product_variant_id); $i++) {
            $cart_data = [
                'user_id' => $data->user_id,
                'product_variant_id' => $product_variant_id[$i],
                'qty' => $qty[$i],
                'is_saved_for_later' => (isset($data->is_saved_for_later) && !empty($data->is_saved_for_later) && $data->is_saved_for_later== '1') ? $data['is_saved_for_later'] : '0',
            ];

            if (DB::table('cart')->where('user_id',$data->user_id)->where('product_variant_id',$product_variant_id[$i])->count() > 0) {
                DB::table('cart')->where('user_id',$data->user_id)->where('product_variant_id',$product_variant_id[$i])->update($cart_data);
            } else {
                DB::table('cart')->insert($cart_data);
            }
        }
         return false;
    }

    public static function validate_stock($product_variant_ids, $qtns)
    {
        /*
            --First Check => Is stock management active (Stock type != NULL) 
            Case 1 : Simple Product 		
            Case 2 : Variable Product (Product Level,Variant Level) 			

            Stock Type :
                0 => Simple Product(simple product)
                    -Stock will be stored in (product)master table	
                1 => Product level(variable product)
                    -Stock will be stored in product_variant table	
                2 => Variant level(variable product)		
                    -Stock will be stored in product_variant table	
            */
            
    
        $response = array();
        $is_exceed_allowed_quantity_limit = false;
        $error = false;
        for ($i = 0; $i < count($product_variant_ids); $i++) {
            $res =DB::table('ec_products')->where('id', $product_variant_ids[$i])->get();
            

            if ($res[0]->with_storehouse_management= 0 ) {
                //Case 1 : Simple Product(simple product)
                
                if ($res[0]->stock_status == 'out_of_stock') {
                            $error = true;
                            break;
                }
                
            }    
                //Case 2 & 3 : Product level(variable product) ||  Variant level(variable product)
            else{
                if($res[0]->allow_checkout_when_out_of_stock==0){
                    $stock = intval($res[0]->quantity) - intval($qtns[$i]);
                    if ($stock < 0) {
                        $is_exceed_allowed_quantity_limit=true;
                        $error = true;
                        break;
                    }
                }   
            }
            
        }

        if ($error) {
            $response['error'] = true;
            if ($is_exceed_allowed_quantity_limit) {
                $response['message'] = "One of the products quantity exceeds the allowed limit.Please deduct some quanity in order to purchase the item";
            } else {
                $response['message'] = "One of the product is out of stock.";
            }
        } else {
            $response['error'] = false;
            $response['message'] = "Stock available for purchasing.";
        }
        // print_r($response);
        return $response;
    }
    public static function get_cart_total($user_id, $product_variant_id = false, $is_saved_for_later = '0', $address_id = '')
{
    
    $query=DB::table('ec_products as p')
    ->join('ec_product_variations as pv','p.id','=','pv.product_id')
   
    ->leftJoin('cart','cart.product_variant_id','=','p.id')
    ->select([DB::raw('DISTINCT(p.id)'),
    DB::raw('(select sum(qty)  from cart where user_id="' . $user_id . '" and qty!=0  and  is_saved_for_later = "' . $is_saved_for_later . '" ) as total_items'),
    DB::raw('(select count(id) from cart where user_id="' . $user_id . '" and qty!=0 and  is_saved_for_later = "' . $is_saved_for_later . '" ) as cart_count'),
    'p.quantity as qty',
    'p.sku as slug',
    'p.name',
    'p.description as short_description',
    'p.price',
    'p.sale_price',
    'p.content as description',
    'pv.configurable_product_id as product_id',
   
    'p.is_variation',]
);



    if ($product_variant_id == true) {
    $query=$query->where('cart.product_variant_id',$product_variant_id)
    ->where('cart.user_id',$user_id)
    ->where('cart.qty','!=',0);

    } else {
    $query=$query->where('cart.user_id',$user_id)
    ->where('cart.qty','!=',0);
    }

    if ($is_saved_for_later == 0) {
        $query=$query->where('is_saved_for_later', 0);
    } else {
        $query=$query->where('is_saved_for_later', 1);
    }

   
    $query=$query->orderBy('cart.id', "DESC");
    $data = $query->get()->toarray();
   
    // print_r($t->db->last_query());
    $total = array();
    $variant_id = array();
    $quantity = array();
    $percentage = array();
    $amount = array();
    $cod_allowed = 1;

    foreach ($data as $i => $value) {
        
    
        $data[$i]->tax_percentage=Cart::get_tax_percentage($value->product_id);

        $prctg = (isset($data[$i]->tax_percentage) && intval($data[$i]->tax_percentage) > 0 && $data[$i]->tax_percentage != null) ? $data[$i]->tax_percentage : '0';
       
        $price_tax_amount = $data[$i]->price * ($prctg / 100);
        $special_price_tax_amount = $data[$i]->sale_price* ($prctg / 100);
        
        // $data[$i]['image_sm'] = get_image_url($data[$i]['image'], 'thumb', 'sm');
        // $data[$i]['image_md'] = get_image_url($data[$i]['image'], 'thumb', 'md');
        // $data[$i]['image'] = get_image_url($data[$i]['image']);
        

        
            $cod_allowed = 0;
        

        $variant_id[$i] = $data[$i]->id;
        $quantity[$i] = intval($data[$i]->qty);
        if (floatval($data[$i]->sale_price) > 0) {
            $total[$i] = floatval($data[$i]->sale_price + $special_price_tax_amount) * $data[$i]->qty;
        } else {
            $total[$i] = floatval($data[$i]->price+ $price_tax_amount) * $data[$i]->qty;
        }
        $data[$i]->sale_price= $data[$i]->sale_price+ $special_price_tax_amount;
        $data[$i]->price= $data[$i]->price+ $price_tax_amount;

        $percentage[$i] = (isset($data[$i]->tax_percentage) && floatval($data[$i]->tax_percentage) > 0) ? $data[$i]->tax_percentage: 0;
        if ($percentage[$i] != NUll && $percentage[$i] > 0) {
            $amount[$i] = round($total[$i] *  $percentage[$i] / 100, 2);
        } else {
            $amount[$i] = 0;
            $percentage[$i] = 0;
        }

       // $data[$i]->product_variants= get_variants_values_by_id($data[$i]->id);
    }
    $total = array_sum($total);

    // if (!empty($address_id)) {
    //     $delivery_charge = get_delivery_charge($address_id, $total);
    // }

    // $delivery_charge = str_replace(",", "", $delivery_charge);
    $overall_amt = 0;
    $tax_amount = array_sum($amount);
    $overall_amt = $total;
    $data=(array)$data;
   // $data[0]->is_cod_allowed= $cod_allowed;
    $data['sub_total'] = strval($total);
    $data['quantity'] = strval(array_sum($quantity));
    $data['tax_percentage'] = strval(array_sum($percentage));
    $data['tax_amount'] = strval(array_sum($amount));
    $data['total_arr'] = $total;
    $data['variant_id'] = $variant_id;
   // $data['delivery_charge'] = $delivery_charge;
    $data['overall_amount'] = strval($overall_amt);
    $data['amount_inclusive_tax'] = strval($overall_amt + $tax_amount);
    return $data;
}
    public static function get_tax_percentage($id)
    {
       $res=DB::table('ec_products as p')
        ->leftJoin('ec_taxes as tax','p.tax_id','=','tax.id')
        ->select('tax.percentage')
        ->where('p.id',$id)
        ->get()->toarray();
        
    
        return $res[0]->percentage;
    }
}
