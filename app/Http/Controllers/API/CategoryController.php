<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Ec_product_category;
use RvMedia;

class CategoryController
{
    public function getCat(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'has_child_or_item'=>'nullable|string',    
            'limit'=>'nullable|integer',
            'offset'=>'nullable|integer',
            'order'=>'nullable|string',
            'sort'=>'nullable|string',
            'id'=>'nullable|integer',
          ]);
      

        if ($validator->fails()){
            $this->response['error'] = true;
            $this->response['message'] ="Something is wrong";
            $this->response['data'] = array();
            return response()->json($this->response);
            
        }
        

             $has_child_or_item =isset($request->has_child_or_item)?$request->has_child_or_item:"true";
             $offset = isset($request->offset)? $request->offset: 0;
             $limit = isset($request->limit)? $request->limit: 25;
             $order = isset($request->order)? $request->order:'ASC';
             $sort = isset($request->sort)? $request->sort:'order';
             $id = isset($request->id)? $request->id: '';

        $categories= Ec_product_category::limit($limit)->offset($offset)->where("status","published")->where("parent_id",0)->orderBy($sort,$order)->get();
        $total= Ec_product_category::where("status","published")->count();   

   

        if(!$categories->isEmpty()){
            $message="Cateogry(s) retrieved successfully!";
        }
        else{
            $message="Category retrieved no data";
        }
        $data=  Ec_product_category::get_category_json_data($categories,$id, $limit, $offset, $sort, $order,$has_child_or_item,0);

            return response()->json(
                [
                    'message'=>$message,
                    'error'=>false,
                    'total'=>$total,
                    'data'=>$data,
    
                ], 200);
    }
  
}
