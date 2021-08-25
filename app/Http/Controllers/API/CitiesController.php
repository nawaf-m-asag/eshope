<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Fun;
use Illuminate\Support\Facades\DB;
class CitiesController extends Controller
{
    public function getCities(Request $request)
    {
        /*
            limit:10 {optional}
            offset:0 {optional}
        */
        $validator = Validator::make($request->all(), [
            'limit'=>'nullable|integer',    
            'offset'=>'nullable|integer',
          ]);

        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] = $validator->errors()->first();
        } else {

            $limit = (isset($request->limit) && !empty(trim($request->limit))) ? $request->limit : 25;
            $offset = (isset($request->offset) && !empty(trim($request->offset))) ?  $request->offset: 0;
            $cities = DB::table('cities as c')->select('c.id','c.name')
            ->limit($limit)->offset($offset)
            ->join('areas as a', 'c.id','=','a.city_id')
            ->groupBy('c.id','c.name')
            ->get()->toArray();
            $this->response['error'] = false;
            $this->response['data'] = $cities;
            return response()->json($this->response);
        }
    }   
}
