<?php

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function($request)
{   
    try{
        Log::info($_SERVER['REQUEST_URI']);
        Log::info("Before filter");
        if(!empty(Input::all())){
            Log::info("API Hit" , Input::all());
        }else if(!empty(Input::json()->all())){
            Log::info("API Hit" , Input::json()->all());
        }
        Log::info(apache_request_headers());
    }catch(Exception $e){
        Log::info("Error in before filter");
        Log::info($e);
    }
});


App::after(function($request, $response)
{   
    try{
        Log::info("after filter");
        $reqClient = Request::all();
    
        if(!(isset($reqClient) && isset($reqClient['third_party']) && $reqClient['third_party'])) {
            refreshToken($response);
        }
    }catch(Exception $e){
        Log::info($e);
        Log::info("Error in after filter");
    }
    
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

Route::filter('auth', function()
{
    if (Auth::guest())
    {
        if (Request::ajax())
        {
            return Response::make('Unauthorized', 401);
        }
        else
        {
            return Redirect::guest('login');
        }
    }
});


Route::filter('auth.basic', function()
{
    return Auth::basic();
});

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest', function()
{
    if (Auth::check()) return Redirect::to('/');
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function()
{
    if (Session::token() !== Input::get('_token'))
    {
        throw new Illuminate\Session\TokenMismatchException;
    }
});

Route::filter('validatetoken',function(){

    $data = Request::header('Authorization');

    if(isset($data) && !empty($data)){
        $jwt_token  =   $data;
        $jwt_key    =   Config::get('app.jwt.key');
        $jwt_alg    =   Config::get('app.jwt.alg');

        try{
            if(Cache::tags('blacklist_customer_token')->has($jwt_token)){
                return Response::json(array('status' => 400,'message' => 'User logged out'),400);
            }
            $decoded = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));
            if(!empty($decoded)){
                $GLOBALS['decoded_token'] = $decoded;
            }
        }catch(DomainException $e){
            return Response::json(array('status' => 400,'message' => 'Token incorrect'),400);
        }catch(ExpiredException $e){
            JWT::$leeway = (86400*565);

            $decoded = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));
            // Log::info("Yes4");
            // return $decoded;
            // return Response::json(array('status' => 400,'message' => 'Token expired'),400);
        }catch(SignatureInvalidException $e){
            return Response::json(array('status' => 400,'message' => 'Signature verification failed'),400);
        }catch(Exception $e){
            return Response::json(array('status' => 400,'message' => 'Token incorrect'),400);
        }
    }else{
        return Response::json(array('status' => 400,'message' => 'Empty token or token should be string'),400);
    }

});

Route::filter('jwt',function(){
    $data = Request::header('Authorization');
    $client = Request::header('Client');
    if(isset($client) && !empty($client)){
        if(isset($data) && !empty($data)){
            $jwt_token  =   $data;
            $jwt_key    =   (Config::get('jwt.'.$client.'.key') != '') ? Config::get('jwt.'.$client.'.key') : 'notfound';
            $jwt_alg    =   (Config::get('jwt.'.$client.'.alg') != '') ? Config::get('jwt.'.$client.'.alg') : 'notfound';
            try{
                JWT::decode($jwt_token, $jwt_key,array($jwt_alg));
            }catch(DomainException $e){
                Log::info('DomainException: ',[$e]);
                return Response::json(array('status' => 400),400);
            }catch(ExpiredException $e){
                Log::info('ExpiredException: ',[$e]);
                return Response::json(array('status' => 401),401);
            }catch(SignatureInvalidException $e){
                Log::info('SignatureInvalidException: ',[$e]);
                return Response::json(array('status' => 402),402);
            }catch(Exception $e){
                Log::info('Exception: ',[$e]);
                return Response::json(array('status' => 403),403);
            }
        }else{
            return Response::json(array('status' => 404),404);
        }
    }else{
        return Response::json(array('status' => 405),405);
    }
});



Route::filter('validatevendor',function(){
    
    $jwt_token = Request::header('Authorization');

    if(isset($jwt_token) && !empty($jwt_token)){
        $jwt_key    =   Config::get('jwt.vendorpanel.key');
        $jwt_alg    =   Config::get('jwt.vendorpanel.alg');

        try{
            $decoded = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));
        }catch(DomainException $e){
            return Response::json(array('status' => 401,'message' => 'Token incorrect'),401);
        }catch(ExpiredException $e){
            return Response::json(array('status' => 401,'message' => 'Token expired'),401);
        }catch(SignatureInvalidException $e){
            return Response::json(array('status' => 401,'message' => 'Signature verification failed'),401);
        }catch(Exception $e){
            return Response::json(array('status' => 401,'message' => 'Token incorrect'),401);
        }
    }else{
        return Response::json(array('status' => 401,'message' => 'Empty token or token should be string'),401);
    }

});

Route::filter('device',function(){

    $header_array = array_only(apache_request_headers(), [ "Device-Type", "Device-Model", "App-Version", "Os-Version", "Device-Token", "Device-Id"]);
    
    $flag = false;
    
    foreach ($header_array as $key => $value) {

        if($value != "" && $value != null && $value != 'null'){
           $flag = true;
        }else{
            $header_array[$key] = "";
        }
        
    }

    Log::info('header_array',$header_array);

    $customer_id = "";

    $jwt_token = Request::header('Authorization');

    if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

        Log::info('_device_filter_jwt_token : '.$jwt_token);

        $decoded = customerTokenDecode($jwt_token);
        $customer_id = (int)$decoded->customer->_id;
    }

    $data = [];

    $data['customer_id'] = $customer_id;
    $data['device_id'] = $header_array['Device-Id'];
    $data['os_version'] = $header_array['Os-Version'];
    $data['app_version'] = $header_array['App-Version'];
    $data['device_model'] = $header_array['Device-Model'];
    $data['device_type'] = $header_array['Device-Type'];
    $data['reg_id'] = $header_array['Device-Token'];

    if($flag){

        $device = false;

        if(isset($data['device_id']) && $data['device_id'] != "" && $data['device_id'] != null && $data['device_id'] != 'null'){

            $device = Device::find((int)$data['device_id']);
        }

        if(!$device && isset($data['reg_id']) && $data['reg_id'] != '' && $data['reg_id'] != null && $data['reg_id'] != 'null'){

            $device = Device::where('reg_id', $data['reg_id'])->orderBy('updated_at', 'desc')->first();
        }

        if ($device) {

            if(isset($data['reg_id']) && $data['reg_id'] != '' && $data['reg_id'] != null && $data['reg_id'] != 'null'){
                $device->reg_id = $data['reg_id'];
            }

            if(isset($data['customer_id']) && $data['customer_id'] != '' && $data['customer_id'] != null && $data['customer_id'] != 'null'){
                $device->customer_id = (int)$data['customer_id'];
            }

            if(isset($data['device_model']) && $data['device_model'] != '' && $data['device_model'] != null && $data['device_model'] != 'null'){
                $device->device_model = $data['device_model'];
            }

            if(isset($data['app_version']) && $data['app_version'] != '' && $data['app_version'] != null && $data['app_version'] != 'null'){
                $device->app_version = (float)$data['app_version'];
            }

            if(isset($data['os_version']) && $data['os_version'] != '' && $data['os_version'] != null && $data['os_version'] != 'null'){
                $device->os_version = (float)$data['os_version'];
            }

            if(isset($data['device_type']) && $data['device_type'] != '' && $data['device_type'] != null && $data['device_type'] != 'null'){
                $device->type = $data['device_type'];
            }

            $device->last_visited_date = time();

            $device->update();

        } else {

            $device_id = Device::maxId() + 1;
            $device = new Device();
            $device->_id = $device_id;

            if(isset($data['reg_id']) && $data['reg_id'] != '' && $data['reg_id'] != null && $data['reg_id'] != 'null'){
                $device->reg_id = $data['reg_id'];
            }

            if(isset($data['customer_id']) && $data['customer_id'] != '' && $data['customer_id'] != null && $data['customer_id'] != 'null'){
                $device->customer_id = (int)$data['customer_id'];
            }

            if(isset($data['device_model']) && $data['device_model'] != '' && $data['device_model'] != null && $data['device_model'] != 'null'){
                $device->device_model = $data['device_model'];
            }

            if(isset($data['app_version']) && $data['app_version'] != '' && $data['app_version'] != null && $data['app_version'] != 'null'){
                $device->app_version = (float)$data['app_version'];
            }

            if(isset($data['os_version']) && $data['os_version'] != '' && $data['os_version'] != null && $data['os_version'] != 'null'){
                $device->os_version = (float)$data['os_version'];
            }

            if(isset($data['device_type']) && $data['device_type'] != '' && $data['device_type'] != null && $data['device_type'] != 'null'){
                $device->type = $data['device_type'];
            }

            $device->last_visited_date = time();

            $device->status = "1";
            $device->save();
        }

        $device_id = (int)$device->_id;

        //echo"<pre>";print_r($device_id);exit;

        App::after (function($request, $response) use($device_id)
        {
            $response->headers->set('Device-Id',$device_id);

            return $response;
        });
    }

});
