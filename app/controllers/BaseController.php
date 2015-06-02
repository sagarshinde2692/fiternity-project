<?php

class BaseController extends Controller {

	protected $perpage;
	protected $redis;

    /**
     * @constructor
     */
    public function __construct() {

     	//echo "call in base";
     	//$this->perpage =  Config::get('app.perpage');
     	$this->redis = Redis::connection();
    }


}