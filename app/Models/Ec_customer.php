<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Hash;
use RvMedia;
class Ec_customer extends Model
{

   
    protected $fillable = [      
   
        'country_code',  
        'name',
        'phone',    
        'dob',
        'email',
        'confirmed_at',
        'password',
        'city',
        'area',
        'street',
        'fcm_id',
        'referral_code',
        'friends_code',
        'latitude',
        'longitude',
        'password',
        'pincode',
        'ip_address',
        'created_on',
        'active'
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

        $user_details =DB::table('ec_customers as ec')->selectRaw('ec.id,ec.ip_address,ec.name as username,ec.email,ec.phone as mobile,ec.avatar as image,ec.balance,ec.activation_selector,ec.activation_code,ec.forgotten_password_selector,ec.forgotten_password_code,ec.forgotten_password_time,ec.remember_selector,ec.remember_code,ec.created_on,ec.last_login,ec.active,ec.company,ec.address,ec.bonus,ec.dob,ec.country_code,c.name as city_name,a.name as area_name,ec.street,ec.pincode,ec.apikey,ec.referral_code,ec.friends_code,ec.fcm_id,ec.latitude,ec.longitude,ec.created_at')
        ->where('ec.id',$customer_id)
        ->leftJoin('cities as c', 'c.id','=','ec.city')
        ->leftJoin('areas as a', 'a.id','=','ec.area')
        ->get();
        $user_details[0]->image = RvMedia::getImageUrl($user_details[0]->image,null, false, RvMedia::getDefaultImage());
        $user_details[0]->image_sm= RvMedia::getImageUrl($user_details[0]->image,'small', false, RvMedia::getDefaultImage());
        return $user_details;

    }
   

    public static function getIp(){
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }
        return request()->ip(); // it will return server ip when no client ip found
    }

    protected function _set_password_db($identity, $password)
	{
		$hash = $this->hash_password($password, $identity);

		if ($hash === FALSE)
		{
			return FALSE;
		}

		// When setting a new password, invalidate any other token
		$data = [
			'password' =>bcrypt($password),
			'remember_code' => NULL,
			'forgotten_password_code' => NULL,
			'forgotten_password_time' => NULL
		];

		$this->trigger_events('extra_where');

		$this->db->update($this->tables['login_users'], $data, [$this->identity_column => $identity]);

		return $this->db->affected_rows() == 1;
	}
}
