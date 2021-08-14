<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RvMedia;
use App\Models\Meta_box;

class Ec_product_category extends Model
{
  


   public static  function get_category_json_data($categories,$id, $limit, $offset, $sort, $order,$has_child_or_item,$level)
   {
      
   
    $data=[];

        foreach ($categories as $key => $value) {
           
                if($value->status=="published")
                $status="1";
                else
                $status="0";
                $children=[];

                if($has_child_or_item=="false"){
                
                $children= Ec_product_category::where("status","published")->where("parent_id",$value->id)->get();
                if($children!="[]"){
                    $level++;
                    $children= Ec_product_category::get_category_json_data($children,$id, $limit, $offset, $sort, $order,$has_child_or_item,$level);
                    $level--;
                }
            }

            $json_opj=
            [
                'id'=>"$value->id",
                'name'=>$value->name,
                "parent_id"=>"$value->parent_id",
                "slug"=>str_replace(' ', '_', $value->name),
                "image"=>RvMedia::getImageUrl($value->image, 'small', false, RvMedia::getDefaultImage()),
                "banner"=>RvMedia::getImageUrl($value->image, 'small', false, RvMedia::getDefaultImage()),
                "row_order"=>"$value->order",
                "status"=>$status,
                "children"=>$children,
                "text"=>$value->name,
                "state"=>["opened"=>true],
                "level"=>$level,     
            ];
            if($level==0){

                $json_opj+=[
                  "icon"=>"jstree-folder",  
                ];
              }
              
            $data[$key]=$json_opj;
        }
        
    return  $data;
   }

   public function Category()
   {
       return $this->belongsToMany('App\Models\Ec_product');
   }
 
}
