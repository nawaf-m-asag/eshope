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
            $query=$query->orderBy($sort, $order);
        }
    
        $res =  $query->get()->toArray();
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
   
public static function delete_details($where, $table)
{
    
    if (DB::table($table)->where($where)->delete()) {
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





    public static function reset_password($identity, $new) {
	
		

        if($res =DB::table('ec_customers')->where('phone',$identity)->update(['password'=>bcrypt($new)]))
        {
            return true;
        }
        return false;
		
	}

    public static function get_settings($type = 'system_settings', $is_json = false)
    {
       
        $res = DB::table('app_settings')->select('*')->where('variable', $type)->get()->toarray();
        if (!empty($res)) {
            if ($is_json) {
                return json_decode($res[0]->value, true);
            } else {
                return output_escaping($res[0]->value);
            }
        }
    }
    //update_wallet_balance
    public static function update_wallet_balance($operation, $user_id, $amount, $message = "Balance Debited")
    {

    
        $user_balance =DB::table('ec_customers')->select('balance')->where(['id' => $user_id])->get()->toArray();
        if (!empty($user_balance)) {

            if ($operation == 'debit' && $amount > $user_balance[0]->balance) {
                $response['error'] = true;
                $response['message'] = "Debited amount can't exceeds the user balance !";
                $response['data'] = array();
                return $response;
            }

            if ($user_balance[0]->balance >= 0) {
                $data = [
                    'transaction_type' => 'wallet',
                    'user_id' => $user_id,
                    'type' => $operation,
                    'amount' => $amount,
                    'message' => $message,
                ];
                if ($operation == 'debit') {
                    $data['message'] = (isset($message)) ? $message : 'Balance Debited';
                    $data['type'] = 'debit';
                    DB::table('ec_customers')->update('balance', '`balance` - ' . $amount)->where('id', $user_id);
                } else {
                    $data['message'] = (isset($message)) ? $message : 'Balance Credited';
                    $data['type'] = 'credit';
                    DB::table('ec_customers')->update('balance', '`balance` + ' . $amount)->where('id', $user_id);
                }            
                $data = escape_array($data);
               // $t->db->insert('transactions', $data);
                $response['error'] = false;
                $response['message'] = "Balance Update Successfully";
                $response['data'] = array();
            } else {
                $response['error'] = true;
                $response['message'] = ($user_balance[0]->balance != 0) ? "User's Wallet balance less than " . $user_balance[0]->balance. " can be used only" : "Doesn't have sufficient wallet balance to proceed further.";
                $response['data'] = array();
            }
        } else {
            $response['error'] = true;
            $response['message'] = "User does not exist";
            $response['data'] = array();
        }
        return $response;
    }
    public static function  getCartCount($user_id,$id){
        
            $ci = DB::table('cart');
           
            $ci= $ci->where('user_id', $user_id)->where('product_variant_id', $id);;
           
            $res= $ci->where('qty','>', 0)
            ->where('is_saved_for_later', 0)
            ->distinct()->select('qty')->get()->toArray();
            if(!empty($res)&&isset($res[0]->qty)){
                return strVal($res[0]->qty);
            }
            else{
                return "0";
            }
    }

    
   
}
