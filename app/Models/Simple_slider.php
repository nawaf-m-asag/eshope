<?php
namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simple_slider extends Model
{

    public static  function get_slider_data()
   {

   $data= DB::table('simple_sliders as ss')->join('simple_slider_items as ssi','ssi.simple_slider_id','=','ss.id')->orderBy('order')->where('ss.status','published')->get();

    return $data;
   }
}
