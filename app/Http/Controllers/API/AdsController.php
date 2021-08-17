<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ad;

use RvMedia;
class AdsController extends Controller
{
    public function getOfferImages(Request $request)
    {
$data=[];
        $res=Ad::get_offer_images();
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
