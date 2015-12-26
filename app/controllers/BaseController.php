<?php

class BaseController extends Controller {

	protected $perpage;

	protected $statusCode = 200;

    /**
     * @constructor
     */
    public function __construct() {

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
    

}