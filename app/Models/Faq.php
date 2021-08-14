<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{

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
