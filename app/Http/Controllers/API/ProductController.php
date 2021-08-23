<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Ec_product;

class ProductController extends Controller
{
    public function getProduct(Request $request)
    {
        $error=false;
        $filters=[];
        $validator = Validator::make($request->all(), [
            'id'=>'nullable|integer',    
            'search'=>'nullable|string',
            'category_id'=>'nullable|integer',
            'attribute_value_ids'=>'nullable|string',
            'sort'=>'nullable|string',
            'limit'=>'nullable|integer',
            'offset'=>'nullable|integer',    
            'order'=>'nullable|string',
            'is_similar_products'=>'nullable|integer',
            'top_rated_product'=>'nullable|string',
            'user_id'=>'nullable|string',
          ]);
      

        if ($validator->fails()){
            $this->response['error'] = true;
            $this->response['message'] =$validator->errors()->first();
            $this->response['data'] = array();
            return response()->json($this->response);
            
        }
        else{
            
            $limit =isset($request->limit)?$request->limit:100;
            $offset = isset($request->offset)? $request->offset: 0;
            $order = isset($request->order)? $request->order: 'ASC';
            $sort = isset($request->sort)? $request->sort:'order';
            $filters['search'] = isset($request->search)? $request->search:null;
            $filters['tags']= isset($request->tags)? $request->tags:"";
            $filters['attribute_value_ids'] = (isset($request->attribute_value_ids)) ? $request->attribute_value_ids: null;
            $filters['is_similar_products']= isset($request->is_similar_products)? $request->is_similar_products: null;
            $filters['product_type'] = isset($request->product_type )? $request->product_type :null;
            $category_id = isset($request->category_id)? $request->category_id:null;
            $product_id = isset($request->product_id)? $request->product_id:null;
            $customer_id = isset($request->user_id)? $request->user_id:null;


            $products = Ec_product::fetch_product_json_data($customer_id, (isset($filters)) ? $filters : null, $product_id, $category_id, $limit, $offset, $sort, $order);

           
            if (!empty($products['product'])) {
                $this->response['error'] = false;
                $this->response['message'] = "Products retrieved successfully !";
                $this->response['search'] = (isset($products['search']) && !empty($products['search'])) ? $products['search'] :null;
                $this->response['filters'] = (isset($products['filters']) && !empty($products['filters'])) ? $products['filters'] : [];
                $this->response['total'] = (isset($products['total'])) ? strval($products['total']) : '';
                $this->response['offset'] = (isset($request->offset) && !empty($request->offset)) ? $request->offset : '0';
                $this->response['data'] = $products['product'];
            } else {
                $this->response['error'] = true;
                $this->response['message'] = "Products Not Found !";
                $this->response['data'] = array();
            }
        }
        return response()->json($this->response);
     
    }  
    
  
}
