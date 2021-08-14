<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ec_wish_list;

class Wish_listsController extends Controller
{
    public function _setFav(Request $request)
    {
        $error=false;
      
        $customer_id = $request->input('user_id');
        $product_id = $request->input('product_id');
  
        $data=[
            'customer_id'=>$customer_id,    
            'product_id'=>$product_id,
            ];
            
  
        $ec_wish_lists=Ec_wish_list::create($data);
    
             if($ec_wish_lists){
                $message="ok to favorite";
            }
            else{
                $message="Not Added to favorite";
                $error=true;
            }
            
            return response()->json(
                [
                    'error'=>$error,
                    'message'=>$message,
                    'data'=>[],
                ], 200);

    }  
    
    public function _removeFav(Request $request)
    {
        $error=false;
        $customer_id = $request->input('user_id');
        $product_id = $request->input('product_id');
        $data=[
            'customer_id'=>$customer_id,    
            'product_id'=>$product_id,
            ];
            
  
        $ec_wish_lists=Ec_wish_list::where('customer_id',$customer_id)->where('product_id',$product_id)->delete();
    
             if($ec_wish_lists){
                $message="Removed from favorite";
            }
            else{
                $message="Not Removed from favorite";
                $error=true;
            }
            
            return response()->json(
                [
                    'error'=>$error,
                    'message'=>$message,
                    'data'=>[],
                ], 200);

    }  
}
