<?PHP namespace App\Services;

use \Vendoruser;
use \JWT;
use Config, Response, Validator;

Class Jwtauth {

    public function errorMessage($errors){

        $message = [];
        $errors = json_decode(json_encode($errors));
        foreach ($errors as $key => $value) {
            $message[$key] = $value[0];
        }
        // $message = implode(',', array_values($message));anjay@123
        return $message;
    }

    public function vendorLogin( $credentials ){

        $rules      =   ['email' => 'required|email', 'password' => 'required' ];
        $validator  =   Validator::make($credentials, $rules);
        if($validator->fails()) {
            $data = array( 'message' => $this->errorMessage($validator->errors()), 'status_code' => 400 );
            return  Response::json($data, 400);
        }

        $vendoruser = Vendoruser::where('email','=',$credentials['email'])->where('status','=','1')->first();
        if(empty($vendoruser)){
            $data = ['status_code' => 400,'message' => ['error' => 'Customer does not exists'] ];
            return  Response::json( $data, 400);
        }

        if($vendoruser['password'] != md5($credentials['password'])){
            $data = ['status_code' => 400,'message' => ['error' => 'Incorrect email or password'] ];
            return  Response::json( $data, 400);
        }

        //return $vendoruser;
        $vendorToken    =   $this->createToken($vendoruser);
        $data           =   ['status_code' => 200, 'message' => 'Successfull Login :)', 'token' => $vendorToken];
        return  Response::json( $data, 200);
    }

    private function createToken($vendoruser){

        $vendoruserArr          =   $vendoruser->toArray();
        $vendorData             =   [];
        $vendorData['name']     =   (isset($vendoruserArr['name'])) ? $vendoruserArr['name'] : "";
        $vendorData['email']    =   (isset($vendoruserArr['email'])) ? $vendoruserArr['email'] : "";
        $vendorData['_id']      =   (isset($vendoruserArr['_id'])) ? $vendoruserArr['_id'] : "";

        $jwt_payload = array(
            "iat"       =>  Config::get('jwt.vendorpanel.iat'),
            "nbf"       =>  Config::get('jwt.vendorpanel.nbf'),
            "exp"       =>  Config::get('jwt.vendorpanel.exp'),
            "vendor"    =>  $vendorData
        );

        $jwt_key    =   Config::get('jwt.vendorpanel.key');
        $jwt_alg    =   Config::get('jwt.vendorpanel.alg');
        $token      =   JWT::encode($jwt_payload, $jwt_key, $jwt_alg);
        return $token;
    }




}                                       