<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Fun;
class Setting extends Model
{

   public static function get_settings($type = 'system_settings', $is_json = false)
    {
    
    
        $res   = DB::table('app_settings')->select('*')->where('variable', $type)->get()->toArray();
        if (!empty($res)) {
           
            if ($is_json) {
            
                return json_decode($res[0]->value, true);
            } else {
                return Fun::output_escaping($res[0]->value);
            }
            
        }
    }
    
   
   
}
