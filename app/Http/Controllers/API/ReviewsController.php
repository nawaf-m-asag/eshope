<?php

namespace App\Http\Controllers\API;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ec_review;


class ReviewsController extends Controller
{
    public function getReview(Request $request)
    {
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
    public function set_product_rating()
    {
      

        $validator = Validator::make($request->all(), [
            'user_id'=>'integer',    
            'product_id'=>'integer',
            'rating'=>'integer',
            'comment'=>'nullable|string',
           
          ]);

      
        if ($validator->fails()) {
            $response['error'] = true;
            $response['message'] = strip_tags(validation_errors());
            $response['data'] = array();
            return response()->json($this->response);
        } else {
           

            $res = DB::select('*')->join('product_variants pv', 'pv.id=oi.product_variant_id')->join('products p', 'p.id=pv.product_id')->where(['pv.product_id' => $_POST['product_id'], 'oi.user_id' => $_POST['user_id'], 'oi.active_status!=' => 'returned'])->limit(1)->get('order_items oi')->result_array();
            if (empty($res)) {
                $response['error'] = true;
                $response['message'] = 'You cannot review as the product is not purchased yet!';
                $response['data'] = array();
                echo json_encode($response);
                return;
            }

            $rating_data = fetch_details(['user_id' => $_POST['user_id'], 'product_id' => $_POST['product_id']], 'product_rating', 'images');
            $this->rating_model->set_rating($_POST);
            $rating_data = $this->rating_model->fetch_rating((isset($_POST['product_id'])) ? $_POST['product_id'] : '', '', '25', '0', 'id', 'DESC');
            $rating['product_rating'] = $rating_data['product_rating'];
            $rating['no_of_rating'] = $rating_data['rating'][0]['no_of_rating'];
            $response['error'] = false;
            $response['message'] = 'Product Rated Successfully';
            $response['data'] = $rating;
            echo json_encode($response);
            return;
        }
    }
}
