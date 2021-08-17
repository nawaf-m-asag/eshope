<?php
namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{

    public static  function get_offer_images()
   {

   $data= DB::table('ads as a')->select('*')->where('status','published')
   ->orderBy('order')->where('expired_at','>=',date('Y-m-d').' 00:00:00')
   ->get();

    return $data;
   }
}
