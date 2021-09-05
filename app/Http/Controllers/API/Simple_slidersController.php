<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Simple_slider;
use App\Models\Ec_product;
use App\Models\Ec_product_category;
use Illuminate\Support\Facades\DB;
use RvMedia;
class Simple_slidersController extends Controller
{
    public function getSlider(Request $request)
    {
        $data=[];
        $res=Simple_slider::get_slider_data();
        $link_name="";
      
        foreach ($res as $i=> $row) {
         
            $data=[];
            $type='default';
            $type_id='0';
            $link_name="";
            $link=[];
            $link = explode('/', $res[$i]->link);
           
            if((isset($link[0])&&isset($link[1]))||(isset($link[1])&&isset($link[2]))){
               
                if(($link[1]=='products'||$link[1]=='product-categories')&&isset($link[2]))
                    $link_name =$link[2];
                  
                if(($link[0]=='products'||$link[0]=='product-categories')&&isset($link[1])) 
                    $link_name =$link[1];
               
             $query=DB::table('slugs')->where('key',$link_name)->select('reference_id','prefix')->limit(1)->get();

  
                if(isset($query[0]->reference_id)){
                    
                    if($query[0]->prefix=='products'){
                        
                        $pro_res = Ec_product::fetch_product_json_data(null,null, $query[0]->reference_id);
                        $data=$pro_res['product'];
                        $type='products';
                        $type_id=strval($query[0]->reference_id);
                    }  
                    else if($query[0]->prefix=='product-categories'){
                        $cat_res= Ec_product_category::get_category_json_data($query[0]->reference_id);
                        $data=$cat_res;
                        $type='categories';
                        $type_id=strval($query[0]->reference_id);
                    }  
                }
            } 
        $json_data[$i]['id']=strval($row->id);
        $json_data[$i]['date_added']=$row->created_at;
        $json_data[$i]['image']= RvMedia::getImageUrl($row->image,null, false, RvMedia::getDefaultImage());
        $json_data[$i]['type']= $type;
        $json_data[$i]['type_id']=$type_id;
        $json_data[$i]['data']= $data;
        }

    $response['error'] = false;
    $response['data'] = $json_data;
    return response()->json($response);
    }  
  
    

}