<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Hash;
class Ec_customer extends Model
{

   
    protected $fillable = [      
   
        'country_code',  
        'name',
        'phone',    
        'dob',
        'email',
        'confirmed_at',
        'password'
      
    ];

    public static function change_password($identity, $old, $new)
	{
        $res =DB::table('ec_customers')->where('id',$identity)->select('password')->get();
        if(Hash::check($old,$res[0]->password)){
           
            $res =DB::table('ec_customers')->where('id',$identity)->update(['password'=>bcrypt($new)]);
            return  true;
         }
        return false;
    }
    public static function get_customer_data_by_id($customer_id)
    {
        $data =DB::table('ec_customers as ec')->selectRaw('ec.id,ec.ip_address,ec.name as username,ec.email,ec.phone as mobile,ec.avatar as image,ec.balance,c.name as city_name,a.name as area_name')
        ->where('ec.id',$customer_id)
       // ->where('add.is_default',1)
        ->leftJoin('addresses as add', 'add.user_id','=','ec.id')
        ->leftJoin('cities as c', 'c.id','=','add.city_id')
        ->leftJoin('areas as a', 'a.id','=','add.area_id')
        ->get();

        dd($data);
        $user_details[0]->image = RvMedia::getImageUrl($user_details[0]->avatar,null, false, RvMedia::getDefaultImage());
                   
   
        $user_details[0]->image_sm= RvMedia::getImageUrl($user_details[0]->avatar,'small', false, RvMedia::getDefaultImage());
    }
    
}
