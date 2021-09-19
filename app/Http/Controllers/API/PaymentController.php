<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Fun;
use Illuminate\Support\Facades\DB;
use RvMedia;
class PaymentController extends Controller
{
     //get_notifications()
     public  function getTransaction(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'sort'=>'numeric',    
             'limit'=>'numeric',
             'user_id'=>'required|numeric',
            
           ]);
 
 
 
         if ($validator->fails()) {
             $this->response['error'] = true;
             $this->response['message'] = $validator->errors()->first();
             $this->response['data'] = array();
         } else {
             $limit = (isset($request->limit) && is_numeric($request->limit) && !empty(trim($request->limit))) ? $request->limit : 25;
             $offset = (isset($request->offset) && is_numeric($request->offset) && !empty(trim($request->offset))) ? $request->offset : 0;
             $user_id = (isset($request->user_id) && !empty(trim($request->user_id))) ? $request->user_id: null;
             $res = Payment::get_transactions($user_id, $offset, $limit);

             $this->response['error'] = false;
             $this->response['message'] = 'Notification Retrieved Successfully';
             $this->response['total'] = !empty($res['data']) ? $res['total'] : 0;
             $this->response['data'] = !empty($res['data']) ? $res['data'] : [];
                 
         }
         return response()->json($this->response);
     }    
     public  function payment_bank_transfer_description()
     {
       
            $description=Fun::fetch_details(['key' => 'payment_bank_transfer_description'], 'settings', 'value');
    
             $this->response['error'] = false;
             $this->response['message'] = 'Payment bank transfer description Retrieved Successfully';
             $this->response['data'] =$description;
                 
         
         return response()->json($this->response);
     }   

}