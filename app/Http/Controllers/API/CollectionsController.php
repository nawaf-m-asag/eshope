<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ec_product_collections;
use App\Models\Ec_product;

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
        $this->response['message'] = "error";
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


    $collections=Ec_product_collections::select('*');
    if(isset($section_id) && !empty($section_id)){
         $collections=$collections->where('id',$section_id);
    }
    $collections=$collections->where("status","published")->limit($limit)->offset($offset)->get();
    $collections_array= $collections->toarray();
    if(!empty($collections_array)){
        foreach ($collections as $key => $collection) {

            $query=DB::
            table('ec_product_collections as epc')
            ->Join('ec_product_collection_products as epcp','epcp.product_collection_id','=','epc.id')
            ->Join('ec_products as p','p.id','=','epcp.product_id') 
            ->Join('ec_product_category_product as cp','cp.product_id','=','p.id')
                ->leftJoin('ec_product_categories as c',function($query){
        
                    $query->on('c.id','=', DB::raw('(SELECT cp2.category_id FROM ec_product_category_product as cp2 WHERE p.id = cp2.product_id LIMIT 1)'));
            })->where('epcp.product_collection_id',$collection->id)
            ->leftJoin('ec_taxes as tax','p.tax_id','=','tax.id')
            ->select([DB::raw('DISTINCT(p.id)'),
            'p.name as product_name',
            'p.tax_id',
            'p.quantity',
            'p.description as short_description',
            'p.sku',
            'c.id as category_id',
            'p.content as description',
            'p.order',
            'c.name as category_name',
            'p.status',
            'p.content',
            'p.images',
            'tax.percentage',
            'p.is_variation',
            'epc.id as epc_id',
            'epc.name as epc_name',
            'epc.description as epc_description',
            'epc.created_at as epc_created_at',
            
           ]
        )->where("p.status","published")->orderBy($p_sort,$p_order)->limit($p_limit)->offset($p_offset)->get();
        
        $total = 0;
        $res= $query->toarray();
        
       
                if (!empty($res)) {
                    $pro_details = Ec_product::get_products_By_ids($query,$user_id);
                
                    $total=DB::table('ec_product_collection_products')->where('product_collection_id',$collection->id)->count();
                        
                    
                        $ids="";
                        foreach ($pro_details['product']as $i => $item) {
                            if($i==0)
                            $ids.=$pro_details['product'][$i]['id'];
                            else
                            $ids.=','.$pro_details['product'][$i]['id'];
    
                        }
    
                        $data[$key]['id']="$collection->id";
                        $data[$key]['title']=$collection->name;
                        $data[$key]['short_description']=$collection->description;
                        $data[$key]['style']="default";
                        $data[$key]['product_ids']=$ids;
                        $data[$key]['row_order']="0";
                        $data[$key]['categories']=null;
                        $data[$key]['product_type']="custom_products";
                        $data[$key]['date_added']=$collection->created_at->format('Y-m-d H:i:s');
                        
                        $data[$key]['total']= "$total";
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