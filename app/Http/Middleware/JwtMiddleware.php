<?php

namespace App\Http\Middleware;
use App\Http\Controllers\Controller;
use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use App\Models\Fun;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
class JwtMiddleware extends BaseMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
    
    try {
        $token = JWTAuth::getToken();
    } catch (Exception $e) {
        $response['error'] = true;
        $response['message'] = $e->getMessage();
        
        return response()->json($response);
    }

    if (!empty($token)) {

        $api_keys = Fun::fetch_details(['status' => 1], 'client_api_keys');
        if (empty($api_keys)) {
            $response['error'] = true;
            $response['message'] = 'No Client(s) Data Found !';
                
        return response()->json($response);
        }
    // JWT::$leeway = 60;
        $flag = true; //For payload indication that it return some data or throws an expection.
        $error = true; //It will indicate that the payload had verified the signature and hash is valid or not.
        foreach ($api_keys as $row) {
            $message = '';
            try {
                $payload = JWTAuth::decode($token,$row->secret)->toArray();
            
                if (isset($payload['iss']) && $payload['iss'] == 'eshop') {
                    return $next($request);
                    
                } else {
                    $error = true;
                    $flag = false;
                    $message = 'Invalid Hash';
                    break;
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
            }
        }

        if ($flag) {
            $response['error'] = true;
            $response['message'] = $message;
            return response()->json($response);
        } else {
            if ($error == true) {
                $response['error'] = true;
                $response['message'] = $message;
                return response()->json($response);
            } 
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Unauthorized access not allowed";
        return response()->json($response);
    }
}
    
}