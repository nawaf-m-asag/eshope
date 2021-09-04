<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use JWTAuth;
use  App\Models\Fun;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
class Faq extends Model
{


    public function __construct()
    {
      
    }
   
    public static  function get_faqs_json_data($faqs)
   {

    $data=[];

        foreach ($faqs as $key => $value) {
           
            $json=
            [
                'id'=>"$value->id",
                'question'=>$value->question,
                "answer"=>$value->answer,
                "status"=>"1",
            ];

            $data[$key]=$json;
 
        }
        
     
    return  $data;


   }
}
