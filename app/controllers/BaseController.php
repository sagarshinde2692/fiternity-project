<?php

class BaseController extends Controller {

	protected $perpage;

    protected $statusCode = 200;
    
    public $device_type;
    public $app_version;
    public $device_id;
    public $device_token;

    /**
     * @constructor
     */
    public function __construct() {
        $this->device_type = Request::header('Device-Type');
        $this->app_version = Request::header('App-Version');
        $this->authorization = Request::header('Authorization');
        $this->mobile_verified = Request::header('Mobile-Verified');
        $this->device_id = !empty(Request::header('Device-Id'))? Request::header('Device-Id'): null;

        $this->get_device_type = !empty($_GET['device_type']) ? $_GET['device_type'] : '' ;
        $this->get_app_version = !empty($_GET['app_version']) ? $_GET['app_version'] : '' ;
        $this->device_token = !empty(Request::header('Device-Token')) ? Request::header('Device-Token'): null;
		// Log::info($_SERVER['REQUEST_URI']);
     	//echo "call in base";
     	//$this->perpage =  Config::get('app.perpage');
    }


    public function getStatusCode (){
    	return $this->statusCode;
    }

    public function setStatusCode ($statusCode){
    	
    	$this->statusCode = $statusCode;
    	return $this;
    }

    public function responseNotFound ($message = 'Not Found'){
    	return   $this->setStatusCode(404)->respondWithError($message);
    }

    public function responseEmpty ($message = 'No Result'){
        return   $this->setStatusCode(200)->respondWithError($message);
    }

     public function responseMissingData ($message = 'Missing Data',$showoriginalMsg = false){
        if($showoriginalMsg){
            return   $this->setStatusCode(404)->respondWithError($message);
        }else {
            return   $this->setStatusCode(404)->respondWithError("Data Missing - ".$message);
        }
    }
    

    public function respond ($data, $header = []){
    	return  Response::json($data, $this->getStatusCode(), $header);
    }


    public function respondWithError ($message){
    	return   $this->respond(array(
    		'error' => array(
    			'message' => $message,
    			'status_code' => $this->getStatusCode() 
    			)
    		));
    }
    



    
    /**
     * Calculate the number of seconds with the given delay.
     *
     * @param  \DateTime|int  $delay
     * @return int
     */
    protected function getSeconds($delay){

        if ($delay instanceof DateTime){
            return max(0, $delay->getTimestamp() - $this->getTime());
        }

        if ($delay instanceof \Carbon\Carbon){
            return max(0, $delay->timestamp - $this->getTime());
        }
        // echo (int) $delay; exit;
        return (int) $delay;
    }

    /**
     * Get the current UNIX timestamp.
     *
     * @return int
     */
    public function getTime(){
        return time();
    }

    public function errorMessage($errors){

        $errors = json_decode(json_encode($errors));
        $message = array();
        foreach ($errors as $key => $value) {
            $message[$key] = $value[0];
        }

        $message = implode(',', array_values($message));

        return $message;
    }


}