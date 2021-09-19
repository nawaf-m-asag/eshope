<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\Fun;
use Illuminate\Support\Facades\DB;
class AddressController  extends Controller
{
    public function addNewAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'=>'required|integer',    
            'type'=>'nullable',
            'country_code'=>'nullable',    
            'name'=>'nullable',
            'mobile'=>'nullable|integer',    
            'alternate_mobile'=>'nullable|integer',
            'address'=>'nullable',    
            'landmark'=>'nullable',
            'area_id'=>'nullable',    
            'city_id'=>'nullable',
            'pincode'=>'nullable|integer',
            'state'=>'nullable',    
            'country'=>'nullable',
            'latitude'=>'nullable',    
            'longitude'=>'nullable',
          ]);


        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] = $validator->errors()->first();
            $this->response['data'] = array();
        } else {
            Address::set_address($request);
            $res = Address::get_address($request->user_id, false, true);
           
            $this->response['error'] = false;
            $this->response['message'] = 'Address Added Successfully';
            $this->response['data'] = $res;
        }
        return response()->json($this->response);
       
    }   
    public function deleteAddress(Request $request)
    {
      //if (!$this->verify_token()) {
          //   return false;
    //   }
    $validator = Validator::make($request->all(), [
        'id'=>'required|integer',   
      ]);
    
        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] = $validator->errors()->first();
            $this->response['data'] = array();
        } else {
           Address::where('id',$request->id)->delete();
            $this->response['error'] = false;
            $this->response['message'] = 'Address Deleted Successfully';
            $this->response['data'] = array();
        }
        return response()->json($this->response);
    }

     //get_address
     public function getAddress(Request $request)
     {
         /*
             user_id:3    
         */
   

         $validator = Validator::make($request->all(), [
            'user_id'=>'required|numeric',   
          ]);
         if ($validator->fails()) {
             $this->response['error'] = true;
             $this->response['message'] = $validator->errors()->first();
             $this->response['data'] = array();
         } else {
             $res = Address::get_address($request->user_id);
             $is_default_counter = array_count_values(array_column($res, 'is_default'));
             if (!isset($is_default_counter['1']) && !empty($res)) {
                
                 Fun::update_details(['is_default' => '1'], ['id' => $res[0]['id']], 'addresses');
                 $res = Address::get_address($request->user_id);
             }
             if (!empty($res)) {
                 $this->response['error'] = false;
                 $this->response['message'] = 'Address Retrieved Successfully';
                 $this->response['data'] = $res;
             } else {
                 $this->response['error'] = true;
                 $this->response['message'] = "No Details Found !";
                 $this->response['data'] = array();
             }
         }
         return response()->json($this->response);
     }




     //update_address
    public function update_address(Request $request)
    {
   
        $validator = Validator::make($request->all(), [
            'user_id'=>'required|numeric',    
            'type'=>'nullable',
            'country_code'=>'nullable',    
            'name'=>'nullable',
            'mobile'=>'nullable|numeric',    
            'alternate_mobile'=>'nullable|integer',
            'address'=>'nullable',    
            'landmark'=>'nullable',
            'area_id'=>'nullable',    
            'city_id'=>'nullable',
            'pincode'=>'nullable|numeric',
            'state'=>'nullable',    
            'country'=>'nullable',
            'latitude'=>'nullable',    
            'longitude'=>'nullable',
          ]);

        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] = $validator->errors()->first();
            $this->response['data'] = array();
        } else {
            Address::set_address($request);
            $res = Address::get_address(null, $request->id, true);
            $this->response['error'] = false;
            $this->response['message'] = 'Address updated Successfully';
            $this->response['data'] = $res;
        }
        return response()->json($this->response);
    }


}

