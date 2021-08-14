<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;

class FaqsController extends Controller
{
    public function getFaqs(Request $request)
    {
        $faqs= Faq::where("status","published")->orderBy("id","desc")->get();
        $total= Faq::where("status","published")->count();
             

            $data=Faq::get_faqs_json_data($faqs);
            return response()->json(
                [
                    'message'=>'FAQ(s) Retrieved Successfully',
                    'error'=>false,
                    'total'=>"$total",
                    'data'=>$data,
                ], 200);

    }   
}
