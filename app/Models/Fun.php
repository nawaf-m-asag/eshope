<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Fun extends Model
{

  
    public static function output_escaping($array)
    {
        if (!empty($array)) {
            if (is_array($array)) {
                $data = array();
                foreach ($array as $key => $value) {
                    $data[$key] = stripcslashes($value);//use to clear text from slashes Ex:hello / world => hello world 
                }
                return $data;
            } else if (is_object($array)) {
                $data =[];
                foreach ($array as $key => $value) {
                    $data[$key] = stripcslashes($value);
                }
                return $data;
            } else {
                return stripcslashes($array);
            }
        }
    }
   public static function fetch_details($where= NULL, $table, $fields = '*', $limit = '', $offset = '', $sort = '', $order = '')
    {
        
        $query=DB::table($table)->selectRaw($fields);
        if (!empty($where)){
            $query=$query->where($where);
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
    public static function update_details($set,$where, $table, $escape = true)
{
    
   

    $query=DB::table($table)->where($where)->update($set);
  
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
public static function escape_array($array)
{
    $posts = array();
    if (!empty($array)) {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $posts[$key] = escape_str($value);
            }
        } else {
            return escape_str($array);
        }
    }
    return $posts;
}
public static function is_exist($where, $table, $update_id = null)
{
    
    $where_tmp = [];
    foreach ($where as $key => $val) {
        $where_tmp[$key] = $val;
    }
  
    if ($update_id == null ?DB::table($table)->where($where)->count() > 0 : DB::table($table)->where($where)->whereNotIn('id', $update_id)->count() > 0) {

        return true;
    } else {
       
        return false;
    }

    }
   
}
