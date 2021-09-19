<?php

namespace App\Http\Controllers\API;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Ec_review;


class ReviewsController extends Controller
{
    /*--user-rating
        -set_product_rating
        -delete_product_rating
        -get_product_rating 
    */ 
    
    public function getReview(Request $request)
    {
       // get product rating
        $product_id = isset($request->product_id)? $request->product_id: null;
        $offset = isset($request->offset)? $request->offset: 0;
        $limit = isset($request->limit)? $request->limit: 30;
        $total= Ec_review::where("status","published")->where("product_id",$product_id)->count();

        if($total!=0){
                $reviews= Ec_review::limit($limit)->offset($offset)->where("status","published")->where("product_id",$product_id)->orderBy("created_at")->get();
                $no_of_rating= Ec_review::where("status","published")->where("comment",'!=',"")->where("product_id",$product_id)->count();
                $data=Ec_review::get_reviews_json_data($reviews);
            if(!$reviews->isEmpty()){
                $this->response['message'] = 'Rating retrieved successfully';
                $this->response['no_of_rating'] = $no_of_rating;
                $this->response['total'] ="$total";
                $this->response['total_images'] ="0";
                $this->response['data'] =$data;
                $this->response['error'] = false;     

            }
        }
        else{
            $this->response['message'] = 'No ratings found !';
            $this->response['no_of_rating'] = array();
            $this->response['data'] = array();
            $this->response['error'] = true;
            }
            return response()->json($this->response);
            
    } 
    public function setRating(Request $request)
    {
        //Set Rating product use when product status is completed 
        $validator = Validator::make($request->all(), [
            'user_id'=>'required|integer',    
            'product_id'=>'required|integer',
            'rating'=>'required',
            'comment'=>'nullable', 
          ]);

          $user_id =$request->user_id;
          $product_id =$request->product_id;
          $rating =$request->rating;
          $comment = isset($request->comment)? $request->comment:null;
         
        if ($validator->fails()) {
            $response['error'] = true;
            $response['message'] = $validator->errors()->first();
            $response['data'] = array();
            return response()->json($response);
        } else {
           
            $res = DB::table('ec_products as p')
            ->join('ec_order_product as eop','eop.product_id','=','p.id')
            ->join('ec_orders as eo','eop.order_id','=','eo.id')
            ->where('eo.user_id',$user_id)
            ->where('p.id',$product_id)
            ->where('eo.status','completed')
            ->limit(1)->get()->toArray();
           
            if (empty($res)) {
                
                $response['error'] = true;
                $response['message'] = 'You cannot review as the product is not purchased yet!';
                $response['data'] = array();
                return response()->json($response);
            }
     
            $data=[
                'customer_id'=>$user_id,    
                'product_id'=>$product_id,
                'star'=>$rating,
                'comment'=>$comment,
                ];
                
      
            $ec_wish_lists=Ec_review::create($data);
            $total= Ec_review::where("status","published")->where("product_id",$product_id)->count();

            $reviews= Ec_review::limit(25)->offset(0)->where("status","published")->where("product_id",$product_id)->where("comment",'!=',"")->orderBy("created_at")->get();
            $rating_data['product_rating']=Ec_review::get_reviews_json_data($reviews);
            $rating_data['no_of_rating']= Ec_review::where("status","published")->where("comment",'!=',"")->where("product_id",$product_id)->count();
            $response['error'] = false;
            $response['message'] = 'Product Rated Successfully';
            $response['data'] = $rating_data;
            return response()->json($response);
        }
    }
    //can't use
    public function delete_product_rating(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'rating_id'=>'required|integer',   
          ]);
          $rating_id =$request->rating_id;
        if ($validator->fails()) {
            $response['error'] = true;
            $response['message'] = 'no Deleted Rating';
            $response['data'] = array();
            return response()->json($response);
        } else {
            $Ec_review=Ec_review::where('id',$rating_id)->delete();
            $response['error'] = false;
            $response['message'] = 'Deleted Rating Successfully';
            $response['data'] = array();
            return response()->json($response);
        }
    }
}
