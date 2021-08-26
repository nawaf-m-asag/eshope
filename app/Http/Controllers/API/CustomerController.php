<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ec_customer;
use App\Models\Fun;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    //verify-user
    public function getVerifyUser(Request $request)
    {
        /* Parameters to be passed
            mobile: 9874565478
            email: test@gmail.com 
        */
      //if (!$this->verify_token()) {
          //   return false;
    //   }

        $validator = Validator::make($request->all(), [
            'mobile'=>'required_without:email|numeric',
            'email'=>'required_without:mobile|email',     
          ]);

        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] =  $validator->errors()->first();
            $this->response['data'] = array();
        }
         else {
            if (isset($request->mobile) && Fun::is_exist(['phone' => $request->mobile], 'ec_customers')) {
                $this->response['error'] = true;
                $this->response['message'] = 'Mobile is already registered.Please login again !';
                $this->response['data'] = array();
                
            }
            if (isset($request->email) && Fun::is_exist(['email' => $request->email], 'ec_customers')) {
                $this->response['error'] = true;
                $this->response['message'] = 'Email is already registered.Please login again !';
                $this->response['data'] = array();
                
            }

            $this->response['error'] = false;
            $this->response['message'] = 'Ready to sent OTP request!';
            $this->response['data'] = array();
            
        }
        return response()->json($this->response);
    }
    public function getRegisterUser(Request $request)
    {
      
        $validator = Validator::make($request->all(), [
            'name'=>'required|string',
            'email'=>'required|email|unique:ec_customers,email',     
            'mobile'=>'required|numeric|unique:ec_customers,phone',
            'country_code'=>'required',
            'dob'=>'nullable',
            'city'=>'nullable|numeric',
            'area'=>'nullable|numeric',
            'street'=>'nullable',
            'latitude'=>'nullable',
            'longitude'=>'nullable',
            'password'=>'required',
            'pincode'=>'nullable',
        ],[
            'email.unique'=> 'The email is already registered . Please login', 
            'mobile.unique'=>'The mobile number is already registered . Please login',
           ]
        );

 
        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] =  $validator->errors()->first();;
            $this->response['data'] = array();
        } else {
           

            $email = strtolower($request->email);
            $password = bcrypt($request->password);

            $additional_data = [
                'name' =>  $request->name,
                'phone' =>  $request->mobile,
                'email'=>$email,
                'dob' => (isset($request->dob)  && !empty(trim($request->dob))) ? $request->dob :null,
                'confirmed_at' => date('Y-m-d H:i:s'),
                'password'=>$password,
            ];
            
            $query=Ec_customer::create($additional_data);
            
            $last_added_id =  $query->id;
            $address_data=[
                'user_id'=>  $last_added_id,  
                'name'=>    $request->name, 
                'mobile' =>  $request->mobile,
                'city_id' => (isset($request->city)  && !empty(trim($request->city))) ? $request->city :null,
                'area_id' => (isset($request->area)  && !empty(trim($request->area))) ? $request->area :null,
                'address' => (isset($request->street)  && !empty(trim($request->street))) ? $request->street :'',
                'pincode' => (isset($request->pincode)  && !empty(trim($request->pincode))) ? $request->pincode:null,
                'latitude' => (isset($request->latitude)  && !empty(trim($request->latitude))) ? $request->latitude :null,
                'longitude' =>(isset($request->longitude)  && !empty(trim($request->longitude))) ? $request->longitude :null,
                'is_default'=>1,
                'type'=>'Other',
            ];
            
            $query=Address::create($address_data );
  
            $data =DB::table('ec_customers as ec')->selectRaw('ec.id,ec.name,ec.email,ec.phone,c.name as city_name,a.name as area_name')
            ->where('ec.id',$last_added_id)
            ->leftJoin('addresses as add', 'add.user_id','=','ec.id')
            ->leftJoin('cities as c', 'c.id','=','add.city_id')
            ->leftJoin('areas as a', 'a.id','=','add.area_id')
            ->get();
            $this->response['error'] = false;
            $this->response['message'] = 'Registered Successfully';
            $this->response['data'] = $data;
        }
        return response()->json($this->response);
    }

}
