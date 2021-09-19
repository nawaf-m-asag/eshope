<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ec_product_collections;
use App\Models\Ec_product;
use App\Models\Fun;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use RvMedia;
class CollectionsController extends Controller
{
   public function getSection(Request $request)
   {

   
    $validator = Validator::make($request->all(), [
        'limit'=>'nullable|integer',    
        'offset'=>'nullable|integer',
        'user_id'=>'nullable|integer',
        'section_id'=>'nullable|integer',
        'p_limit'=>'nullable|integer',
        'p_offset'=>'integer|integer',
        'p_sort'=>'nullable|string',    
        'p_order'=>'nullable|string',
        
      ]);

    if ($validator->fails()) {
        $this->response['error'] = true;
        $this->response['message'] = $validator->errors()->first();
        $this->response['data'] = array();
        return response()->json($this->response);
    }
    
    $limit = (isset($request->limit) && is_numeric($request->limit) && !empty(trim($request->limit))) ? $request->limit: 25;
    $offset = (isset($request->offset) && is_numeric($request->offset) && !empty(trim($request->p_limit))) ? $request->offset: 0;
    $user_id = (isset($request->user_id) && !empty(trim($request->user_id))) ? $request->user_id: 0;
    $section_id = (isset($request->section_id) && !empty(trim($request->section_id))) ? $request->section_id: 0;
    $p_limit = (isset($request->p_limit) && !empty(trim($request->p_limit))) ? $request->p_limit: 10;
    $p_offset = (isset($request->p_offset) && !empty(trim($request->p_offset))) ? $request->p_offset: 0;
    $p_order = (isset($request->p_order) && !empty(trim($request->p_order))) ? $request->p_order : 'DESC';
    $p_sort = (isset($request->p_sort) && !empty(trim($request->p_sort))) ? $request->p_sort: 'p.id';
    $filters['attribute_value_ids'] = (isset($request->attribute_value_ids)) ? $request->attribute_value_ids : null;
    $filters['product_type'] = (isset($request->top_rated_product) && $request->top_rated_product== 1) ? 'top_rated_product_including_all_products' : null;

    $collections=Ec_product_collections::select('*');
    if(isset($section_id) && !empty($section_id)){
      
    $collections->where('id',$section_id);
    }
    $collections=$collections->where("status","published")->limit($limit)->offset($offset)->get();
    $collections_array= $collections->toarray();
 
    if(!empty($collections_array)){
      
        foreach ($collections as $key => $collection) {
            $query=null;
            $query=DB::
            table('ec_product_collections as epc')
            ->Join('ec_product_collection_products as epcp','epcp.product_collection_id','=','epc.id')
            ->Join('ec_products as p','p.id','=','epcp.product_id') 
            ->selectRaw('group_concat(DISTINCT(p.id)) as product_ids');
            if(isset($section_id) && !empty($section_id)){
      
                $query->where('epc.id',$collection->id);
                }
             $query=$query->groupBy('epc.id')
            ->where("p.status","published")->get();
            
        $total = 0;

        $res= $query->toArray();
       
                if (!empty($res)&&isset($res[$key]->product_ids)) {
                    $product_ids = explode(',', $res[$key]->product_ids);
                    $product_ids = array_filter($product_ids);
                   
                    $pro_details = Ec_product::fetch_product_json_data($user_id, (isset($filters)) ? $filters : null, (isset($product_ids) && !empty($product_ids)) ? $product_ids : null, null, $p_limit, $p_offset, $p_sort, $p_order);
                
                    $total=DB::table('ec_product_collection_products')->where('product_collection_id',$collection->id)->count();
                        $data[$key]['id']=strval($collection->id);
                        $data[$key]['title']=Fun::output_escaping($collection->name);
                        $data[$key]['short_description']=Fun::output_escaping($collection->description);
                        $data[$key]['style']="default";
                        $data[$key]['product_ids']=$res[$key]->product_ids;
                        $data[$key]['row_order']="0";
                        $data[$key]['categories']=null;
                        $data[$key]['product_type']="custom_products";
                        $data[$key]['date_added']=$collection->created_at->format('Y-m-d H:i:s');
                        
                        $data[$key]['total']=strval($total);
                        $data[$key]['filters']=$pro_details['filters'];
                        $data[$key]['product_details'] =$pro_details['product'];
                }
                else
                {
                   
                        $this->response['error'] = true;
                        $this->response['message'] = "Sections not found";
                        $data[$key]['total'] = "0";
                        $data[$key]['filters'] = [];
                        $data[$key]['product_details'] =[];
                        $this->response['data']=$data;
                        
                }
        
                $this->response['error'] = false;
                $this->response['message'] = "Sections retrived successfully";
                $this->response['data']=$data;
    }
    }else {
        $this->response['error'] = true;
        $this->response['message'] = "No sections are available";
        $this->response['data'] = array();
    }
    
    
return response()->json($this->response);
   }
   
}