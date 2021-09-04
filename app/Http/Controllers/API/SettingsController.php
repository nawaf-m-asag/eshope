<?php

namespace App\Http\Controllers\API;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\Ec_customer;
use App\Models\Cart;
use App\Models\Fun;
use RvMedia;
class SettingsController extends Controller
{
    public function getSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'=>'nullable',    
            'user_id'=>'nullable|numeric',
          
          ]);
          $type = (isset($request->type) && $request->type == 'payment_method') ? 'payment_method' : 'all';
        if ($validator->fails()) {

            $this->response['error'] = true;
            $this->response['message'] = $validator->errors()->first();
            $this->response['data'] = array();
            return response()->json($this->response);
        } else {
            $general_settings = array();

            if ($type == 'all' || $type == 'payment_method') {
                $settings = [
                    'logo' => 0,
                    'privacy_policy' => 0,
                    'terms_conditions' => 0,
                    'fcm_server_key' => 0,
                    'contact_us' => 0,
                    'payment_method' => 1,
                    'about_us' => 0,
                    'currency' => 0,
                    'time_slot_config' => 1,
                    'user_data' => 0,
                    'system_settings' => 1,
                ];

                if ($type == 'payment_method') {

                    $settings_res['payment_method'] =Setting::get_settings($type, $settings[$request->type]);
                  
                    $time_slot_config =Setting::get_settings('time_slot_config', $settings['time_slot_config']);

                    if (!empty($time_slot_config) && isset($time_slot_config)) {
                        $time_slot_config['delivery_starts_from'] = $time_slot_config['delivery_starts_from'] - 1;
                        $time_slot_config['starting_date'] = date('Y-m-d', strtotime(date('d-m-Y') . ' + ' . intval($time_slot_config['delivery_starts_from']) . ' days'));
                    }

                    $settings_res['time_slot_config'] = $time_slot_config;
                    $time_slots = Fun::fetch_details('', 'time_slots', '*', '', '', 'from_time', 'ASC');

                    if (!empty($time_slots)) {
                        for ($i = 0; $i < count($time_slots); $i++) {

                            $datetime = DateTime::createFromFormat("h:i:s a", $time_slots[$i]['from_time']);

                            // if ($datetime <= date('h:i:s a', time()) || date('h:i:s a', time()) > $datetime) {
                            //     unset($time_slots[$i]);
                            // }
                        }
                    }

                    $settings_res['time_slots'] = array_values($time_slots);
                    if (isset($request->user_id) && !empty($request->user_id)) {
                        $cart_total_response = Cart::get_cart_total($request->user_id, false, 0);
                        $cod_allowed = isset($cart_total_response[0]->is_cod_allowed) ? $cart_total_response[0]->is_cod_allowed : 1;
                        $settings_res['is_cod_allowed'] = $cod_allowed;
                    } else {
                        $settings_res['is_cod_allowed'] = 1;
                    }

                    $general_settings = $settings_res;
                } else {

                    foreach ($settings as $type => $isjson) {
                        if ($type == 'payment_method') {
                            continue;
                        }
                        $general_settings[$type] = [];
                        $settings_res =Setting::get_settings($type, $isjson);
                     
                        if ($type == 'logo') {
                            $settings_res = RvMedia::getImageUrl($settings_res,null, false, RvMedia::getDefaultImage());
                        }
                        if ($type == 'user_data' && isset($request->user_id)) {
                            $cart_total_response =Cart::get_cart_total($request->user_id, false, 0);
                            $settings_res = Ec_customer::get_customer_data_by_id($request->user_id);
                            $settings_res[0]->cart_total_items = (isset($cart_total_response[0]->cart_count) && $cart_total_response[0]->cart_count> 0) ? $cart_total_response[0]->cart_count: '0';
                            $settings_res = $settings_res[0];
                        }
                      
                        //Strip tags in case of terms_conditions and privacy_policy
                        // $settings_res = !is_array($settings_res) ? strip_tags($settings_res) : $settings_res;
                        array_push($general_settings[$type], $settings_res);
                        // $general_settings[$type] = $settings_res;
                    }
                }
                  

                $this->response['error'] = false;
                $this->response['message'] = 'Settings retrieved successfully';
                $this->response['data'] = $general_settings;
            } else {
                $this->response['error'] = true;
                $this->response['message'] = 'Settings Not Found';
                $this->response['data'] = array();
            }
            return response()->json($this->response);
        }
    }

}
