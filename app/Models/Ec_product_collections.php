<?php
namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ec_product_collections extends Model
{

    public static  function get_slider_data()
   {

   $data= DB::table('simple_sliders as ss')->join('simple_slider_items as ssi','ssi.simple_slider_id','=','ss.id')->orderBy('order')
   ->where('ss.id',DB::raw('(SELECT MIN(ss2.id) FROM simple_sliders ss2  WHERE ss2.status ="published")'))
   ->get();

    return $data;
   }
}
