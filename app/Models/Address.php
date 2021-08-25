<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Fun;
use Illuminate\Support\Facades\DB;
class Address extends Model
{

    protected $fillable = [
            'user_id',       
            'type',
            'country_code',  
            'name',
            'mobile',    
            'alternate_mobile',
            'address',    
            'landmark',
            'area_id',    
            'city_id',
            'pincode',
            'state',    
            'country',
            'latitude',    
            'latitude',
    ];


   public static function  set_address($data)
    {

        //$data = Fun::escape_array($data);
        $address_data = [];

        if (isset($data['user_id'])) {
            $address_data['user_id'] = $data['user_id'];
        }
        if (isset($data['id'])) {
            $address_data['id'] = $data['id'];
        }
        if (isset($data['type'])) {
            $address_data['type'] = $data['type'];
        }
        if (isset($data['name'])) {
            $address_data['name'] = $data['name'];
        }
        if (isset($data['mobile'])) {
            $address_data['mobile'] = $data['mobile'];
        }
        $address_data['country_code'] = (isset($data['country_code']) && !empty($data['country_code']) && is_numeric($data['country_code'])) ? $data['country_code'] : 0;

        if (isset($data['alternate_mobile'])) {
            $address_data['alternate_mobile'] = $data['alternate_mobile'];
        }

        if (isset($data['address'])) {
            $address_data['address'] = $data['address'];
        }

        if (isset($data['landmark'])) {
            $address_data['landmark'] = $data['landmark'];
        }

        if (isset($data['area_id'])) {
            $address_data['area_id'] = $data['area_id'];
        }

        if (isset($data['city_id'])) {
            $address_data['city_id'] = $data['city_id'];
        }

        if (isset($data['pincode'])) {
            $address_data['pincode'] = $data['pincode'];
        }

        if (isset($data['state'])) {
            $address_data['state'] = $data['state'];
        }

        if (isset($data['country'])) {
            $address_data['country'] = $data['country'];
        }
        if (isset($data['latitude'])) {
            $address_data['latitude'] = $data['latitude'];
        }
        if (isset($data['longitude'])) {
            $address_data['longitude'] = $data['longitude'];
        }


        if (isset($data['id']) && !empty($data['id'])) {
            if (isset($data['is_default']) && $data['is_default'] == true) {
                $address = Fun::fetch_details('id', $data['id'], 'addresses','*');
                DB::table('addresses')->where('user_id', $address[0]->user_id)->update(['is_default' => '0']);
                DB::table('addresses')->where('id', $data['id'])->update(['is_default' => '1']);
            }

           DB::table('addresses')->where('id', $data['id'])->update($address_data);
        } else {
            $query=address::create($address_data);
            $last_added_id =  $query->id;
            if (isset($data['is_default']) && $data['is_default'] == true) {
                DB::table('addresses')->where('user_id', $data['user_id'])->update(['is_default'=> 0]);
                DB::table('addresses')->where('id', $last_added_id)->update(['is_default'=>1]);
            }
        }
    }
    
  public static function get_address($user_id, $id = false, $fetch_latest = false, $is_default = false)
    {
        $where = [];
     
           $query= DB::table('addresses as addr')->selectRaw('addr.*,a.name as area,a.minimum_free_delivery_order_amount,a.delivery_charges,c.name as city')
               // ->where($where)
                ->join('cities as c', 'addr.city_id','=','c.id')
                ->join('areas as a','addr.area_id','=','a.id')
             
              //  ->groupBy('addr.id')
                ->orderBy('addr.id', 'DESC');
                if (isset($user_id) || $id != false) {
                    if (isset($user_id) && $user_id != null && !empty($user_id)) {
                        $query->where('user_id',$user_id);
                    }
                    if ($id != false) {
                        $query->where('addr.id', $id);
                    }
                   
            
            if ($fetch_latest == true) {
                $query->limit(1);
            }
            if (!empty($is_default)) {
                $query->where('is_default', 1);
            }
            $res = $query->get()->toArray();
      
            if (!empty($res)) {
                for ($i = 0; $i < count($res); $i++) {
                    $res[$i] = Fun::output_escaping($res[$i]);
                }
            }
            return $res;
        }
    }
   

   
}
