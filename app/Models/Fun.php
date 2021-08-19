<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Fun extends Model
{

  

   public static function fetch_details($where1=null,$where2 = NULL, $table, $fields = '*', $limit = '', $offset = '', $sort = '', $order = '')
    {
        
        $query=DB::table($table)->selectRaw($fields);
        if (!empty($where1)&&!empty($where2)){
            $query=$query->where($where1,$where2);
        }
    
        if (!empty($limit)) {
            $query=$query->limit($limit);
        }
    
        if (!empty($offset)) {
            $query=$query->offset($offset);
        }
    
        if (!empty($order) && !empty($sort)) {
            $query=$query->order_by($sort, $order);
        }
    
        $res =  $query->get()->toarray();
        return $res;
    }
    public static function update_details($set, $where1,$where2, $table, $escape = true)
{
    
   

    $query=DB::table($table)->where($where1,$where2)->update($set);
  
    $response = FALSE;
    if ($query) {
        $response = TRUE;
    }
    return $response;
}
   
public static function delete_details($where1,$where2, $table)
{
    
    if (DB::table($table)->where($where1,$where2)->delete()) {
        return true;
    } else {
        return false;
    }
}  
    public static function get_product_id($product_variant_id){

       $q= DB::table('ec_products as p')
        ->join('ec_product_variations as pv','pv.configurable_product_id','p.id')
        ->where('pv.product_id',$product_variant_id)->select('p.id')->get()->toarray();
    if(!empty($q))
    return $q[0]->id;
    else
    {
        return $product_variant_id;
    }
    }
}
