<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ec_review;


class ReviewsController extends Controller
{
    public function getReview(Request $request)
    {
        
      
        $request = json_decode(file_get_contents("php://input"));

        $product_id = isset($request->product_id)? $request->product_id: null;
        $offset = isset($request->offset)? $request->offset: 0;
        $limit = isset($request->limit)? $request->limit: 30;
        $reviews= Ec_review::limit($limit)->offset($offset)->where("status","published")->where("product_id",$product_id)->orderBy("created_at")->get();
        $total= Ec_review::where("status","published")->where("product_id",$product_id)->count();
             if(!$reviews->isEmpty()){
                $message="Rating retrieved successfully";
            }
            else{
                $message="Rating retrieved no data";
            }
            $data=Ec_review::get_reviews_json_data($reviews);
            return response()->json(
                [
                    'message'=>$message,
                    'error'=>false,
                    'no_of_rating'=>0,
                    'total'=>"$total",
                    'total_images'=>"0",
                    'data'=>$data,
                ], 200);

    }
  
}
