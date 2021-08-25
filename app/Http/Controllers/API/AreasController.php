<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Fun;
use Illuminate\Support\Facades\DB;
class AreasController  extends Controller
{
    public function getArea(Request $request)
    {
        /* id='57' */

        $validator = Validator::make($request->all(), [
            'id'=>'required|integer',    
          ]);

        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] = $validator->errors()->first();
        } 
        else{
            $areas = Fun::fetch_details('city_id',$request->id,'areas');
            if (!empty($areas)) {
                for ($i = 0; $i < count($areas); $i++) {
                    $areas[$i] =Fun::output_escaping($areas[$i]);
                }
            }
            $this->response['error'] = false;
            $this->response['data'] = $areas;
        }
        return response()->json($this->response);
       
    }   
}

