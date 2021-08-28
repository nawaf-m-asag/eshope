<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ec_customer;
use App\Models\Fun;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RvMedia;
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
        
        $this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', 'Mail', 'trim|required|xss_clean|valid_email|is_unique[users.email]', array('is_unique' => ' The email is already registered . Please login'));
        $this->form_validation->set_rules('mobile', 'Mobile', 'trim|required|xss_clean|max_length[16]|numeric|is_unique[users.mobile]', array('is_unique' => ' The mobile number is already registered . Please login'));
        $this->form_validation->set_rules('country_code', 'Country Code', 'trim|required|xss_clean');
        $this->form_validation->set_rules('dob', 'Date of birth', 'trim|xss_clean');
        $this->form_validation->set_rules('city', 'City', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('area', 'Area', 'trim|numeric|xss_clean');
        $this->form_validation->set_rules('street', 'Street', 'trim|xss_clean');
        $this->form_validation->set_rules('pincode', 'Pincode', 'trim|xss_clean');
        $this->form_validation->set_rules('fcm_id', 'Fcm Id', 'trim|xss_clean');
        $this->form_validation->set_rules('referral_code', 'Referral code', 'trim|is_unique[users.referral_code]|xss_clean');
        $this->form_validation->set_rules('friends_code', 'Friends code', 'trim|xss_clean');
        $this->form_validation->set_rules('latitude', 'Latitude', 'trim|xss_clean');
        $this->form_validation->set_rules('longitude', 'Longitude', 'trim|xss_clean');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');

      
        $validator = Validator::make($request->all(), [
            'name'=>'required|string',
            'email'=>'required|email|unique:ec_customers,email',     
            'mobile'=>'required|numeric|unique:ec_customers,phone',
            'country_code'=>'required',
            'dob'=>'nullable',
            'city'=>'nullable|numeric',
            'area'=>'nullable|numeric',
            'street'=>'nullable',
            'fcm_id'=>'nullable',
            'referral_code'=>'nullable|unique:ec_customers',
            'friends_code'=>'nullable',
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
                'city' => (isset($request->city)  && !empty(trim($request->city))) ? $request->city :null,
                'area' => (isset($request->area)  && !empty(trim($request->area))) ? $request->area :null,
                'street' => (isset($request->street)  && !empty(trim($request->street))) ? $request->street :'',
                'pincode' => (isset($request->pincode)  && !empty(trim($request->pincode))) ? $request->pincode:null,
                'latitude' => (isset($request->latitude)  && !empty(trim($request->latitude))) ? $request->latitude :null,
                'longitude' =>(isset($request->longitude)  && !empty(trim($request->longitude))) ? $request->longitude :null,
                'country_code',
                'fcm_id',
                'referral_code',
                'friends_code',
                'active' => 1


            ];
            
            $query=Ec_customer::create($additional_data);
            
            $last_added_id =  $query->id;
            $address_data=[
                'user_id'=>  $last_added_id,  
                'name'=>    $request->name, 
                'mobile' =>  $request->mobile,
         
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
    
       //update_user
       public function update_user(Request $request)
       {
           /*
               user_id:34
               username:hiten{optional}
               dob:12/5/1982{optional}
               mobile:7852347890 {optional}
               email:amangoswami@gmail.com	{optional}
               address:Time Square	{optional}
               area:ravalwadi	{optional}
               city:23	{optional}
               pincode:56	    {optional}
               latitude:45.453	{optional}
               longitude:45.453	{optional}
               //file
               image:[]
               //optional parameters
               referral_code:Userscode
               old:12345
               new:345234
           */
         //if (!$this->verify_token()) {
             //   return false;
       //   }
       $validator = Validator::make($request->all(), [
        'user_id'=>'required|numeric',
        'username'=>'nullable|string',
        'email'=>'nullable|email|unique:ec_customers,email',     
        'dob'=>'nullable',
        'city'=>'nullable|numeric',
        'area'=>'nullable|numeric',
        'address'=>'nullable',
        'latitude'=>'nullable',
        'longitude'=>'nullable',
        'pincode'=>'nullable',
        'image'=>'nullable',
    ],[
        'email.unique'=> 'The email is already registered . Please login', 
       ]
    );  

        if (!empty($request->old) || !empty($request->new)) {
            $validator= Validator::make($request->all(), [
                'old'=>'required',    
                'new'=>'required|min:6', 
              ]); 
          }
    
   
     
           if ($validator->fails()) {
               
                   $response['error'] = true;
                   $response['message'] = $validator->errors()->first();;
                   return response()->json($response);
               
           } else {
               if (!empty($request->old) || !empty($request->new)) {
                   $res = Fun::fetch_details(['id' => $request->user_id], 'ec_customers');
                  
                   if (!empty($res)) {
                    
                       if (!Ec_customer::change_password($res[0]->id, $request->old,$request->new)) {
   
                           // if the login was un-successful
                           $response['error'] = true;
                           $response['message'] = 'password_change_unsuccessful';
                           return response()->json($response);
                       }
                   } else {
                       $response['error'] = true;
                       $response['message'] = 'User not exists';

                       return response()->json($response);
                   }
               }
              $result_image= RvMedia::handleUpload($request->file('image'), 0, 'cstomers');

            if($result_image['error']&&!empty($_FILES['image']['name']) && isset($_FILES['image']['name']))
            {
                $response['error'] = true;
                $response['message'] ='Profile Image not upload';
                return response()->json($response);
            };
   
               $set = [];
               $address = [];
               if (isset($request->username) && !empty($request->username)) {
                   $set['name'] = $request->username;
               }
               if (isset($request->email) && !empty($request->email)) {
                   $set['email'] = $request->email;
               }
               if (isset($request->dob) && !empty($request->dob)) {
                   $set['dob'] = $this->input->post('dob', true);
               }
               if (isset($request->mobile) && !empty($request->mobile)) {
                   $address['mobile'] = $request->mobile;
               }
               if (isset($request->address) && !empty($request->address)) {
                   $address['address'] = $request->address;
               }
               if (isset($request->city) && !empty($request->city)) {
                   $address['city_id'] = $request->city;
               }
               if (isset($request->area) && !empty($request->area)) {
                   $address['area_id'] = $request->area;
               }
               if (isset($request->pincode) && !empty($request->pincode)) {
                   $address['pincode'] = $request->pincode;
               }
               if (isset($request->latitude) && !empty($request->latitude)) {
                   $address['latitude'] = $request->latitude;
               }
               if (isset($request->longitude) && !empty($request->longitude)) {
                   $address['longitude'] = $request->longitude;
               }
   
               if (!empty($_FILES['image']['name']) && isset($_FILES['image']['name'])) {
                   $set['avatar'] = $result_image['data']['url'];
               }
              //dd($this->getIp());
               if (!empty($set)) {
                
                 DB::table('ec_customers')->where('id', $_POST['user_id'])->update($set);
                   
                  Address::where('user_id', $_POST['user_id'])->where('is_default',1)->update($address);
                   
                
 
                    $data=Ec_customer::get_customer_data_by_id($_POST['user_id']);
                   $response['error'] = false;
                   $response['message'] = 'Profile Update Succesfully';
                   $response['data'] = $data;
                   return response()->json($response);
               }
           }
           
       }
       public function getIp(){
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }
        return request()->ip(); // it will return server ip when no client ip found
    }
}
