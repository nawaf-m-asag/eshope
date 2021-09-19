<?php
namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Ec_product;
use RvMedia;
class Cart extends Model
{
   public static function get_cart_count($user_id)
    {
        $ci = DB::table('cart');
        if (!empty($user_id)) {
            $ci= $ci->where('user_id', $user_id);
        }
        $res= $ci->where('qty','>', 0)
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
            if ($res[0]->with_storehouse_management==0 ) {
                //Case 1 : Simple Product(simple product)
                
                if ($res[0]->stock_status == 'out_of_stock') {
                            $error = true;
                            break;
                }
                
            }    
                //Case 2 & 3 : Product level(variable product) ||  Variant level(variable product)
            else{
                if($res[0]->allow_checkout_when_out_of_stock==0&&$res[0]->with_storehouse_management==1){
                    $stock = intval($res[0]->quantity) - intval($qtns[$i]);
                    if ($stock < 0) {
                        $is_exceed_allowed_quantity_limit=true;
                        $error = true;
                        $stock = intval($res[0]->quantity);
                        break;

                    }
                  
                }   
            }
            
        }

        if ($error) {
            $response['error'] = true;
            if ($is_exceed_allowed_quantity_limit) {
                $response['message'] = "One of the products quantity exceeds the allowed limit($stock).Please deduct some quanity in order to purchase the item";
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
        ->leftJoin('cart','cart.product_variant_id','=','p.id')
        ->rightJoin('ec_products  as pp',function($query){
            $query->on('pp.id','=','cart.product_variant_id');
        })
        ->select([DB::raw('DISTINCT(p.id)'),
        DB::raw('(select sum(qty)  from cart where user_id="' . $user_id . '" and qty!=0  and  is_saved_for_later = "' . $is_saved_for_later . '" ) as total_items'),
        DB::raw('(select count(id) from cart where user_id="' . $user_id . '" and qty!=0 and  is_saved_for_later = "' . $is_saved_for_later . '" ) as cart_count'),
        'cart.user_id',
        'cart.product_variant_id',
        'cart.qty',
        'cart.is_saved_for_later',
        'cart.created_at as date_created',
        'p.sku as slug',
        'p.name',
        'p.description as short_description',
        'p.price',
        'p.sale_price as special_price',
        'p.content as description',
        'p.images',
        ]
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
        $data = $query->get()->toArray();
       
        
        // print_r($t->db->last_query());
        $total = array();
        $variant_id = array();
        $quantity = array();
        $percentage = array();
        $amount = array();
        $cod_allowed = 1;

        foreach ($data as $i => $value) {
            $data[$i]->tax_percentage=Cart::get_tax_percentage($value->id);
           
        //  dd( $data[$i]->tax_percentage);
            //use to get first image in array it is defulte
            $product_images=json_decode( $data[$i]->images);
                $default_imag=null;
                if(!empty($product_images)){
                    
                    foreach ($product_images as $key => $value) {
                        if($value!=null)
                        $default_imag=$value;
                        break;
                     }
                }


            $prctg = (isset($data[$i]->tax_percentage) && intval($data[$i]->tax_percentage) > 0 && $data[$i]->tax_percentage != null) ? $data[$i]->tax_percentage : '0';
        
            if ((isset($data[$i]->is_prices_inclusive_tax) && $data[$i]->is_prices_inclusive_tax== 0) || (!isset($data[$i]->is_prices_inclusive_tax)) && $percentage > 0) {
                $price_tax_amount = $data[$i]->price * ($prctg / 100);
                $special_price_tax_amount = $data[$i]->special_price* ($prctg / 100);
            } else {
                $price_tax_amount = 0;
                $special_price_tax_amount = 0;
            }
          
            
            $data[$i]->image_sm= RvMedia::getImageUrl($default_imag,'small', false, RvMedia::getDefaultImage());
            $data[$i]->image_md= RvMedia::getImageUrl($default_imag,'medium', false, RvMedia::getDefaultImage());
            $data[$i]->image= RvMedia::getImageUrl($default_imag,null, false, RvMedia::getDefaultImage());
            

            
            $cod_allowed = 0;
            

            $variant_id[$i] =(string) $data[$i]->id;
            $quantity[$i] = intval($data[$i]->qty);
            if (floatval($data[$i]->special_price) > 0) {
                $total[$i] = floatval($data[$i]->special_price + $special_price_tax_amount) * $data[$i]->qty;
            } else {
                
                $total[$i] =floatval($data[$i]->price+ $price_tax_amount) *$data[$i]->qty;
            }
            $data[$i]->special_price= $data[$i]->special_price+ $special_price_tax_amount;
            $data[$i]->price= $data[$i]->price+ $price_tax_amount;

            $percentage[$i] = (isset($data[$i]->tax_percentage) && floatval($data[$i]->tax_percentage) > 0) ? $data[$i]->tax_percentage: 0;
        
            if ($percentage[$i] != NUll && $percentage[$i] > 0) {
                $amount[$i] = round($total[$i] *  $percentage[$i] / 100, 2);
            
            } else {
                $amount[$i] = 0;
                $percentage[$i] = 0;
            }

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
        $data['sub_total'] =strval(round($total,2));
        $data['quantity'] = strval(array_sum($quantity));
        $data['tax_percentage'] = strval(array_sum($percentage));
        $data['tax_amount'] = strval(array_sum($amount));
        $data['total_arr'] = $total;
        $data['variant_id'] = $variant_id;
        $data['delivery_charge'] ="0";
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
        ->get()->toArray();
      
        if(isset($res[0]) && $res[0]->percentage==null){
           
        $res=DB::table('ec_products as p')  
        ->leftJoin('ec_product_variations as pv','pv.configurable_product_id','=','p.id')   
        ->leftJoin('ec_taxes as tax','p.tax_id','=','tax.id')
        ->select('tax.percentage')
        ->where('pv.product_id',$id)
        ->get()->toArray();
        }
      

        if(isset($res[0]->percentage)&&$res[0]->percentage!=null)
        {
            return $res[0]->percentage; 
        }
        else
        return 0;
    }
   public static function remove_from_cart($data)
    {
        if (isset($data->user_id) && !empty($data->user_id)) {
           $query= DB::table('cart')->where('user_id', $data->user_id);
            if (isset($data->product_variant_id)) {
                $product_variant_id = explode(',', $data->product_variant_id);
                $query=$query->whereIn('product_variant_id', $product_variant_id);
            }
            return $query->delete();
        } else {
            return false;
        }
    }
    
    public static function get_user_cart($user_id, $is_saved_for_later = 0, $product_variant_id = '')
    {
     

        $q = DB::table('ec_products as p')
        ->join('cart as c','c.product_variant_id','p.id')
        ->where('c.is_saved_for_later',$is_saved_for_later)
        ->where('c.user_id',$user_id)
        ->where('c.qty','!=',0)
        ->where('p.status','published');
        if (!empty($product_variant_id)) {
            $q=$q->where('c.product_variant_id', $product_variant_id)
            ->where('p.id',$product_variant_id);
        }
        $res =  $q->select(
            'c.user_id',
            'c.product_variant_id',
            'p.name',
            'c.qty',
            'c.is_saved_for_later',
            'c.created_at as date_created',
            'images as image', 
            'p.sku as slug',
            'p.description as short_description',
            'p.price',
            'p.sale_price as special_price',
            'p.content as description',
            'p.with_storehouse_management',
            'p.quantity'

        )->orderBy('c.updated_at', 'DESC')->get()->toArray();


        if (!empty($res)) {

            $res = array_map(function ($d) {   
                //use to get first image url in array 
                $product_images=json_decode($d->image);
                $default_imag=null;
                if(!empty($product_images)){
                    
                    foreach ($product_images as $key => $value) {
                        if($value!=null){
                        $default_imag=$value;
                        break;}
                     }
                }
               
                $d=(array)$d;
               $d['product_variant_id']=strval($d['product_variant_id']);
               $d['user_id']=strval($d['user_id']);
               $d['qty']= strval($d['qty']);
               $d['is_saved_for_later']=strval($d['is_saved_for_later']);      
               $d['special_price']= ($d['special_price']!="")?$d['special_price']:0; 
               $d['id']=strval(Fun::get_product_id($d['product_variant_id'])); 
               $d['is_prices_inclusive_tax']="0";
               $d['image']= RvMedia::getImageUrl($default_imag,null, false, RvMedia::getDefaultImage());
               $d['minimum_order_quantity']=strval(1);
               $d['quantity_step_size']= null;
               $d['total_allowed_quantity'] = ($d['with_storehouse_management']==0)?strval($d['quantity']):"12";
               $d['tax_percentage']=strval(Cart::get_tax_percentage($d['id']));
               $d['product_variants']= Ec_product::getVariants(null,$d['product_variant_id']);
               unset($d['with_storehouse_management']);
               unset($d['quantity']);
                return $d;
            }, $res);
        }
       
        return $res;
      
    }
    public static function validate_promo_code($promo_code, $user_id, $final_total)
    {
        if (isset($promo_code) && !empty($promo_code)) {

            //Fetch Promo Code Details
            $promo_code_res =DB::table('ec_discounts as pc')
             ->where('pc.code',$promo_code)
             ->where('pc.start_date','<=',date('Y-m-d H:i:s'))
             ->selectRaw('pc.*')
            
             ->where(function($promo_code_res)
             {
                $promo_code_res->where('pc.end_date',null)->orWhere('pc.end_date','>=',date('Y-m-d H:i:s'));
             })
             ->get()->toArray();
     
            if (isset($promo_code_res[0]->id)) {

                if (intval($promo_code_res[0]->total_used) < intval($promo_code_res[0]->quantity)||$promo_code_res[0]->quantity==null) {

                    if ($final_total >= intval($promo_code_res[0]->min_order_price)) {

                    } else {
                        $response['error'] = true;
                        $response['message'] = 'This promo code is applicable only for amount greater than or equal to ' . $promo_code_res[0]->min_order_price;
                        $response['data'] = array();
                        return $response;
                    }
                } else {

                    $response['error'] = true;
                    $response['message'] = "This promo code is applicable only for first " . $promo_code_res[0]->quantity . " users";
                    $response['data'] = array();
                    return $response;
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'The promo code is not available or expired';
                $response['data'] = array();
                return $response;
            }
        }
        
    }
         
}
