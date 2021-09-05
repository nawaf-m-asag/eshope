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
             $offset = isset($request->offset)? $request->offset:0;
             $limit = isset($request->limit)? $request->limit: 25;
             $order = isset($request->order)? $request->order:'ASC';
             $sort = isset($request->sort)? $request->sort:'c1.order';
             $id = isset($request->id)? $request->id: '';

             $this->response['message'] = "Cateogry(s) retrieved successfully!";
            
          


        $cat_res= Ec_product_category::get_category_json_data($id, $limit, $offset, $sort, $order, trim($has_child_or_item));

        $this->response['error'] = !isset($cat_res[0]) ? true : false;      
        $this->response['total'] = isset($cat_res[0]->total)? $cat_res[0]->total: 0;
        $this->response['message'] = !isset($cat_res[0]) ? 'Category does not exist' : 'Category retrieved successfully';
        $this->response['data'] = $cat_res;
        return response()->json($this->response);
    }
  
}
