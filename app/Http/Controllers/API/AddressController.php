<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Address;
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
            'latitude'=>'nullable',
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
}

