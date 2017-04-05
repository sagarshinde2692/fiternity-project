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
    //
});


App::after(function($request, $response)
{
    //
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

    \Log::info('Authorization-------------'.$data);

    if(isset($data) && !empty($data)){
        $jwt_token  =   $data;
        $jwt_key    =   Config::get('app.jwt.key');
        $jwt_alg    =   Config::get('app.jwt.alg');

        try{
            if(Cache::tags('blacklist_customer_token')->has($jwt_token)){
                return Response::json(array('status' => 400,'message' => 'User logged out'),400);
            }
            $decoded = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));
        }catch(DomainException $e){
            return Response::json(array('status' => 400,'message' => 'Token incorrect'),400);
        }catch(ExpiredException $e){
            return Response::json(array('status' => 400,'message' => 'Token expired'),400);
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
                return Response::json(array('status' => 400),400);
            }catch(ExpiredException $e){
                return Response::json(array('status' => 401),401);
            }catch(SignatureInvalidException $e){
                return Response::json(array('status' => 402),402);
            }catch(Exception $e){
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
