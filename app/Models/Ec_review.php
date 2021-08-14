<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ec_review extends Model
{

    protected $fillable = [
        'customer_id',
        'product_id',
        'star',
        'comment',
    ];

    public function Customer()
    {
        return $this->belongsTo('App\Models\Ec_customer');
    }

    public static function get_review_images($product_id)
    {
        $data=[];
        $no_of_rating=Ec_review::where("status","published")->where('product_id',$product_id)->count();
        if(!empty($no_of_rating)){
            $total_reviews=Ec_review::where("status","published")->where('product_id',$product_id)->where('comment','!=',"")->count();
            $reviews= Ec_review::where("status","published")->where("product_id",$product_id)->orderBy("created_at")->get();
            $data['total_images']="0";
            $data['total_reviews_with_images']="0";
            $data['no_of_rating']="$no_of_rating";
            $data['total_reviews']="$total_reviews";
            $data['product_rating']=Ec_review::get_reviews_json_data($reviews);
            return [$data];
        }
        else
        return $data;
        
    }
    
    public static  function get_reviews_json_data($reviews)
   {

    $data=[];

        foreach ($reviews as $key => $value) {
           
            $json=
            [
                'id'=>"$value->id",
                'user_id'=>"$value->customer_id",
                "product_id"=>"$value->product_id",
                "rating"=>$value->star,
                "images"=>[],
                "comment"=>$value->comment,
                "data_added"=>$value->created_at->format('Y-m-d H:i:s'),
                "user_name"=>$value->Customer->name,
            ];
           
              
            $data[$key]=$json;
            
          
           
        }
        
     
    return  $data;


   }
  

   public static function starTotalByID($id)
   {
       
        $total=0;
        $count=0;
        $stars= Ec_review::where("status","published")->where("product_id",$id)->select('star')->get();
    
            foreach ($stars as $key => $value) {
               
               
                $count++;
                $total+=$value->star;

            }
            if($count>0){
                $review['rating']=round($total/$count,2);
            }
            else{
                $review['rating']=0;
            }
        
        $review['no_of_ratings']= $count;
        
    return $review;
   }

   
   
   
}
