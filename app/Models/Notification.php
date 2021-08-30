<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RvMedia;
class Notification extends Model
{

   public static function  get_notifications($offset, $limit, $sort, $order)
    {
        $notification_data = [];
        $count_res = Notification::selectRaw(' COUNT(id) as `total` ')->get()->toArray();
        $search_res =Notification::selectRaw(' * ')->orderBy($sort, $order)->limit($limit)->offset($offset)->get()->toArray();
        for ($i = 0; $i < count($search_res); $i++) {
            $search_res[$i]['title'] = Fun::output_escaping($search_res[$i]['title']);
            $search_res[$i]['message'] = Fun::output_escaping($search_res[$i]['message']);
            if (empty($search_res[$i]['image'])) {
                $search_res[$i]['image'] = '';
            } else {
                
                $search_res[$i]['image']= RvMedia::getImageUrl($search_res[$i]['image'],null, false, RvMedia::getDefaultImage());
            }
        }
        $notification_data['total'] = $count_res[0]['total'];
        $notification_data['data'] = $search_res;
        return  $notification_data;
    }
  
   
}
