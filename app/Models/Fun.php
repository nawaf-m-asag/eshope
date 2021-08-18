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
   
      
    
}
