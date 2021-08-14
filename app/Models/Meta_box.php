<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meta_box extends Model
{

    public static function get_cat_icon($id)
    {
       $icon=Meta_box::select("meta_value")->where("reference_id",$id)->where('meta_key','icon')->get();

        foreach ($icon as $key => $value) {
           $item=$value->meta_value;
           $item= json_decode($item);
          $out= $item[0];
       }
    
      
       
       return $out;
    }
    
}
