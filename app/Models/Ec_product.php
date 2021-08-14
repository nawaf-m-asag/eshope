<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Ec_review;
use App\Models\Ec_wish_list;

use RvMedia;

class Ec_product extends Model
{

  
    public static function  fetch_product_json_data($user_id = NULL, $filter = NULL, $id = NULL, $category_id = NULL, $limit = NULL, $offset = NULL, $sort = NULL, $order = NULL, $return_count = NULL)
    {
  
       
        $query=DB::table('ec_products as p')
       
        ->Join('ec_product_category_product as cp','cp.product_id','=','p.id')
            ->leftJoin('ec_product_categories as c',function($query){

                $query->on('c.id','=', DB::raw('(SELECT cp2.category_id FROM ec_product_category_product as cp2 WHERE p.id = cp2.product_id LIMIT 1)'));
           
        });
        
        
        $query->leftJoin('ec_taxes as tax','p.tax_id','=','tax.id')
        ->select([DB::raw('DISTINCT(p.id)'),
       
        'p.name as product_name', 
        'p.tax_id',
        'p.quantity',
        'p.description as short_description',
        'p.sku',
        'c.id as category_id',
        'p.content as description',
        'p.order',
        'c.name as category_name',
        'p.status',
        'p.content',
        'p.images',
        'tax.percentage',
        'p.is_variation',
        
        
       ]
    );
    
      
       if ($sort == 'p.price' && !empty($sort) && $sort != NULL) {
        $products=$query->orderBy($sort,$order);
        }
  
        /* || filter product by category id ||*/
        if (isset($category_id) && !empty($category_id)) 
        {
            $products=$query->leftJoin('ec_product_categories as c2',function($query){

                $query->on('cp.category_id','=','c2.id');
           
        })->where(function($query) use ($category_id){
            
                $query->where('c2.id',$category_id);
        
            })->addSelect('c2.id as category_id','c2.name as category_name'); 
        }


          /* || filter product by search  ||*/
        if (isset($filter) && !empty($filter['search'])) {
            
            $products=$query->join('ec_product_tag_product as ptp',function($query){
                $query->on('ptp.product_id', '=', 'p.id');
                $query->Join('ec_product_tags as pt', 'pt.id','=','ptp.tag_id')->addSelect('pt.name');
            });
            
            
            $tags= explode(" ", $filter['search']);
            
            $products=$query->where(function($query) use ($tags,$filter){
                foreach ($tags as $tag) {
                    
                    $query->where('pt.name', 'like','%'.trim($tag).'%');       
                }
                     $query->orwhere('p.name', 'like','%'.trim($filter['search']).'%');
                     
            
            });
         }         

        
                  
                 
        
        
       


        
             /* || filter product by tags name ||*/
            if (isset($filter) && !empty($filter['tags'])) {
                $tags = explode(",", $filter['tags']);
                foreach ($tags as $i => $tag) {
                $products=$query->where('pt.name', 'like', '%'.trim($tag).'%');
            }
           
        }
        

      $products=$query->limit($limit)->where("p.status","published")->where("p.is_variation",0)->get();
     
      $data=[];
      $total=0;
    $a=[];

    
        foreach ($products as $key => $value) {
            
            $a[]=$value->id;
            $total=$key+1;
            $sales=Ec_product::getSalesCount($value->id);
            if($sales==0)
                $sales=1;
            if($value->status=="published")
                 $status="1";
            else
                 $status="0";

            $product_images=json_decode($value->images);
            $default_imag=null;
            if(!empty($product_images))
            $default_imag=$product_images[0];

            $attributes=  Ec_product::getProAttributes($value->id);
        
            if(!empty($attributes)){

                
                $type="variable_product";
            }else{
                $type="simple_product"; 
            }
            $review= Ec_review::starTotalByID($value->id);

        $data[$key]=[
                    'total'=>"14",
                    'sales'=>"$sales",
                    'stock_type'=>null,
                    'id'=>"$value->id",
                    'name'=>$value->product_name,
        /*static*/  'is_prices_inclusive_tax'=>"0",
                    'type'=>$type,
                    "stock"=>"$value->quantity",
                    "category_id"=>"$value->category_id",
                    "short_description"=>$value->description,
                    "slug"=>$value->sku,
                    "description"=>Ec_product::output_escaping($value->content),
        /*static*/  "total_allowed_quantity"=>"1",
        /*static*/  "minimum_order_quantity"=>"1",
        /*static*/  "quantity_step_size"=>"1",
        /*static*/  'cod_allowed'=>"0",
                    'row_order'=>"$value->order",
                    "rating"=>$review['rating'],
        /*static*/  "no_of_ratings"=>$review['no_of_ratings'],
                    "image"=>RvMedia::getImageUrl($default_imag,null, false, RvMedia::getDefaultImage()),
        /*static*/  "is_returnable"=>"1",
        /*static*/  "is_cancelable"=>"0",
        /*static*/  "cancelable_till"=>"",
        /*static*/  "indicator"=>"0",
                    "other_images"=>Ec_product::getOtherImages($product_images,'small'),
        /*static*/  "video_type"=>"",
        /*static*/  "video"=>"",
                    "tags"=>Ec_product::getTags($value->id),
        /*static*/  "warranty_period"=>"3",
        /*static*/  "guarantee_period"=> "3",
                    "made_in"=>null,
        /*static*/  "availability"=>"1",
                    "category_name"=>$value->category_name,
                    "tax_percentage"=>"$value->percentage",
        /*static*/  "review_images"=>[],
                    "attributes"=> $attributes,
                    "variants"=>Ec_product::getVariants($value->id),
                    "min_max_price"=>Ec_product::getMin_max_price($value->id,$value->percentage),
                    "is_purchased"=> false,
                    "is_favorite"=>Ec_wish_list::is_favorite($value->id,$user_id),
                    "image_md"=>RvMedia::getImageUrl($default_imag,'medium', false, RvMedia::getDefaultImage()),
                    "image_sm"=>RvMedia::getImageUrl($default_imag,'small', false, RvMedia::getDefaultImage()),
                    "other_images_sm"=>Ec_product::getOtherImages($product_images,'small'),
                    "other_images_md"=>Ec_product::getOtherImages($product_images,'medium'),
                    "variant_attributes"=> $attributes
                ];
            
      
            }
         
       // dd($a);
   //  $products['filters']=$products['filters']['ids']=explode(",",$s);     
     $products['total']=$total;   
     $products['product']=$data;
      return $products;
    }


 
    public static function getSalesCount($id)
    {
      return  DB::table('ec_product_variations')->where('configurable_product_id',$id)->count();
    }
    public static function getTags($id)
    {
        $tags=[];
       $query= DB::table('ec_product_tag_product as ptp')->where('ptp.product_id',$id)
      ->join('ec_product_tags as pt','pt.id','=','ptp.tag_id')->select('pt.name')->get();
      foreach ($query as $key => $value) {
          $tags[$key]=$value->name;
      }
      return $tags;
    }

    public static function  getOtherImages($images)
    {
         $image=[];
         foreach ($images as $key => $value) {
             if($key>0)
            $image[$key-1]=RvMedia::getImageUrl($value, 'small', false);
         }

        return $image;
    }
    public static function  getVariantsImages($images,$size)
    {
         $image=[];
         foreach ($images as $key => $value) {
            $image[$key]=RvMedia::getImageUrl($value, $size, false);
         }

        return $image;
    }

    public static function  getProAttributes($id)
    {

       
        $attributes = DB::table('ec_product_variations as pv')
       ->join('ec_product_variation_items as pvi','pvi.variation_id','=','pv.id')->orderBy('pa.id')
       ->join('ec_product_attributes as pa','pa.id','=','pvi.attribute_id')
       ->join('ec_product_attribute_sets as pas','pas.id','=','pa.attribute_set_id')->distinct('p.id')
       ->selectRaw('group_concat(DISTINCT(pa.id)) as ids , group_concat(DISTINCT(pa.title)) as value,pas.title as attr_name,pas.title as name')
        ->groupBy('pas.title',)
        ->where('pv.configurable_product_id',$id)->get();
      

        
    

        return $attributes;
    }

    public static function getVariants($id)
    {
      $variants=  DB::table('ec_products as p')
        ->join('ec_product_variations as pv','p.id','=','pv.product_id')
        ->where('pv.configurable_product_id',$id)
        ->where('p.status','published')
        ->select('p.*')->get();
      
        if($variants=="[]"){
            
         $variants=  DB::table('ec_products as p')->where('p.id',$id)->select('p.*')->get();
        }
        
        $variants_data=[];
        foreach ($variants as $key => $value) {

            $product_images=json_decode($value->images);
            $variants_data[$key]=Ec_product::getVariant_ids($value->id);
           
            $variants_data[$key]+=[

                'id'=>"$value->id",
                'product_id'=>"$id",
                'attribute_value_ids'=>null,
                'attribute_set'=>null,
                "price"=>"$value->price",
                "special_price"=>($value->sale_price>0)?"$value->sale_price":"0",
                "sku"=> $value->sku,
                "images"=>Ec_product::getVariantsImages($product_images,null),
                "availability"=>"1",
                "stock"=>"1",
                "status"=>"1",
                "date_added"=>$value->created_at,
                "images_md"=> Ec_product::getVariantsImages($product_images,'medium'),
                "images_sm"=>Ec_product::getVariantsImages($product_images,'small'),
                "cart_count"=>"0",
                "is_purchased"=>0,

            ];
        }

        return $variants_data;

    }
public static function getVariant_ids($id){

        $json_opj['variant_ids']=null;
        $json_opj['variant_values']=null;
        $json_opj['attr_name']=null;
        $json_opj['variant_ids']=null;

        $query = DB::table('ec_product_variations as pv')
        ->join('ec_product_variation_items as pvi','pvi.variation_id','=','pv.id')->orderBy('pa.id')
        ->join('ec_product_attributes as pa','pa.id','=','pvi.attribute_id')
        ->join('ec_product_attribute_sets as pas','pas.id','=','pa.attribute_set_id')
        ->where('pv.product_id',$id)
        ->selectRaw('pv.product_id,group_concat(pa.id) as variant_ids , group_concat(pa.title) as variant_values,group_concat(pas.title) as attr_name')
       ->groupBy('pv.product_id')
      ->get();

 

      foreach ($query as $key => $value) {

            $json_opj['variant_ids']=$value->variant_ids;
            $json_opj['variant_values']=$value->variant_values;
            $json_opj['attr_name']=$value->attr_name;
      }

      $json_opj['attribute_value_ids']= $json_opj['variant_ids'];
      
     return $json_opj;
    
    }
   public static function output_escaping($array)
{
  

    if (!empty($array)) {
        if (is_array($array)) {
            $data = array();
            foreach ($array as $key => $value) {
                $data[$key] = stripcslashes($value);
            }
            return $data;
        } else if (is_object($array)) {
            $data =[];
            foreach ($array as $key => $value) {
                $data->$key = stripcslashes($value);
            }
            return $data;
        } else {
            return stripcslashes($array);
        }
    }
}
    public static function getMin_max_price($product_id='',$percentage=0){

        $response=  DB::table('ec_products as p')

        ->where('p.status','published')
        ->join('ec_product_variations as pv','p.id','=','pv.product_id')
        ->leftJoin('ec_taxes as tax','p.tax_id','=','tax.id')
        ->select('p.price','p.sale_price','tax.percentage as tax_percentage');
        
       


    if (!empty($product_id)) {
        $response= $response->where('pv.configurable_product_id', $product_id)->get()->toarray();
    }
    if(empty($response)){
        $response=  DB::table('ec_products as p')
        ->where('p.status','published')
        ->where('p.id', $product_id)
        ->leftJoin('ec_taxes as tax','p.tax_id','=','tax.id')
        ->select('p.price','p.sale_price','tax.percentage as tax_percentage')->get()->toarray();
    }
    
 

    
    
    if ($percentage > 0) {
        $price_tax_amount = $response[0]->price * ($percentage / 100);
        $special_price_tax_amount = $response[0]->sale_price * ($percentage / 100);
    } else {
        $price_tax_amount = 0;
        $special_price_tax_amount = 0;
    }
    $response = array_map(function ($value) {
        return (array)$value;
    }, $response);
    

    $data=[];
    if(!empty($response)){
        $data['min_price'] = round(min(array_column($response, 'price')) + $price_tax_amount);
        $data['max_price'] = round(max(array_column($response, 'price')) + $price_tax_amount);
        $data['special_price'] = round(min(array_column($response, 'sale_price')) + $special_price_tax_amount);
        $data['max_special_price'] = round(max(array_column($response, 'sale_price')) + $special_price_tax_amount);
        $data['discount_in_percentage']=Ec_product::find_discount_in_percentage($data['special_price'] + $special_price_tax_amount, $data['min_price'] + $price_tax_amount);
    }
   return $data;
   
}

    public static   function  find_discount_in_percentage($special_price, $price)
    {
        $diff_amount = $price - $special_price;
        return intval(($diff_amount * 100) / $price);
    }


}