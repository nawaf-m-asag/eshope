<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RvMedia;
use App\Models\Fun;
class Ec_product_category extends Model
{
  


   public static  function get_category_json_data( $id = NULL, $limit = '', $offset = '', $sort = 'c1.order', $order = 'ASC', $has_child_or_item = 'true')
   {
      
    $level = 0;        

    $where = (isset($id) && !empty($id)) ? ['c1.id' => $id,'c1.status' => 'published'] : ['c1.parent_id' => 0,'c1.status' =>'published'];
        
    $query=DB::table('ec_product_categories as c1')->select('c1.id','c1.name','c1.parent_id','c1.status','c1.order as row_order','c1.image')
    ->where($where);
   
    if ($has_child_or_item == 'false') {
            $query->leftJoin('ec_product_categories as c2', 'c2.parent_id','=','c1.id');
            $query->leftJoin('ec_product_category_product as pc', 'pc.category_id','=','c1.id')
             ->where(function($query){
                
                     $query->orWhereColumn(['c1.id' => 'pc.category_id', 'c2.parent_id' => 'c1.id'], NULL, FALSE);

              }); 
             $query->groupBy('c1.id','c1.name','c1.parent_id','c1.status','c1.order','c1.image');
             
    }
      
    if (!empty($limit) || !empty($offset)) {
        $query->offset($offset);
        $query->limit($limit);
    }

    $categories= $query->orderBy('c1.'.$sort, $order)->get();
 
    $count_res = Ec_product_category::count();  

    $i = 0;
        foreach ($categories as $p_cat) {
            
            $categories[$i]->slug=str_replace(' ', '_', $p_cat->name);
            $categories[$i]->status="1";
            $categories[$i]->children =Ec_product_category::sub_categories($p_cat->id, $level);
            $categories[$i]->text = Fun::output_escaping($p_cat->name);
            $categories[$i]->name =Fun:: output_escaping($categories[$i]->name);
            $categories[$i]->state = ['opened' => true];
            $categories[$i]->icon = "jstree-folder";
            $categories[$i]->level ="$level";
            $categories[$i]->image = RvMedia::getImageUrl($p_cat->image, 'small', false, RvMedia::getDefaultImage());
            $categories[$i]->banner = RvMedia::getImageUrl($p_cat->image, 'small', false, RvMedia::getDefaultImage());
            $i++;
        }
        if(isset($categories[0])){
			$categories[0]->total =4;
        }
        
 
    return  $categories;
   }
   public static function sub_categories($id, $level)
   {
       $level = $level + 1;
       $data = DB::table('ec_product_categories as c1')->select('c1.id','c1.name','c1.parent_id','c1.status','c1.order as row_order','c1.image')
         ->where(['c1.parent_id' => $id, 'c1.status' => 'published'])->get();

       $i = 0;
       foreach ($data as $p_cat) {
            $data[$i]->slug=str_replace(' ', '_', $p_cat->name);
            $data[$i]->status="1";
            $data[$i]->children =Ec_product_category::sub_categories($p_cat->id, $level);
            $data[$i]->text = Fun::output_escaping($p_cat->name);
            $data[$i]->state = ['opened' => true];
            $data[$i]->level = "$level";
            $data[$i]->image = RvMedia::getImageUrl($p_cat->image, 'small', false, RvMedia::getDefaultImage());
            $data[$i]->banner = RvMedia::getImageUrl($p_cat->image, 'small', false, RvMedia::getDefaultImage());
            $i++;
       }
       return $data;
   }

   public function Category()
   {
       return $this->belongsToMany('App\Models\Ec_product');
   }
 
}
