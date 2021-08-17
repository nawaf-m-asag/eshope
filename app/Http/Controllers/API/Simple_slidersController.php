<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Simple_slider;

use RvMedia;
class Simple_slidersController extends Controller
{
    public function getSlider(Request $request)
    {
$data=[];
        $res=Simple_slider::get_slider_data();
        foreach ($res as $i=> $row) {
            $json_data[$i]['id']="$row->id";
            $json_data[$i]['type']='default';
            $json_data[$i]['type_id']='0';
            $json_data[$i]['date_added']=$row->created_at;
            $json_data[$i]['image']= RvMedia::getImageUrl($row->image,null, false, RvMedia::getDefaultImage());

            
            $json_data[$i]['data'] =  [];
            

           
        }
        $response['error'] = false;
        $response['data'] = $json_data;
        return response()->json($response);
    }   
}
