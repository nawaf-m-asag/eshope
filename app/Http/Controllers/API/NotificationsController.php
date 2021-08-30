<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
class NotificationsController  extends Controller
{
    //get_notifications()
    public  function getNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sort'=>'nullable',    
            'limit'=>'numeric',
            'offset'=>'numeric',
            'order'=>'nullable',
          ]);



        if ($validator->fails()) {
            $this->response['error'] = true;
            $this->response['message'] = $validator->errors()->first();
            $this->response['data'] = array();
        } else {
            $limit = (isset($request->limit) && is_numeric($request->limit) && !empty(trim($request->limit))) ? $request->limit : 25;
            $offset = (isset($request->offset) && is_numeric($request->offset) && !empty(trim($request->offset))) ? $request->offset : 0;
            $order = (isset($request->order) && !empty(trim($request->order))) ? $request->order: 'DESC';
            $sort = (isset($request->sort) && !empty(trim($request->sort))) ?$request->sort : 'id';

            $res = Notification::get_notifications($offset, $limit, $sort, $order);
            $this->response['error'] = false;
            $this->response['message'] = 'Notification Retrieved Successfully';
            $this->response['total'] = $res['total'];
            $this->response['data'] = $res['data'];
        }
        return response()->json($this->response);
    }
}

