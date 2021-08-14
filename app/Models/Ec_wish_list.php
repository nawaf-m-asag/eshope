<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ec_wish_list extends Model
{

    protected $fillable = [
        'customer_id',
        'product_id',
    ];

     public static  function   is_favorite($product_id='',$customer_id=0)
    {
        $is_favorite=0;
        $is_favorite= Ec_wish_list::select('id')->where('product_id',$product_id)->where('customer_id',$customer_id)->count();
        
        if($is_favorite!=0)
        {
            return 1;

        }else{
            
            return 0;
        }
    }
}
