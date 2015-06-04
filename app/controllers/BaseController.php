<?php

class BaseController extends Controller {

	protected $perpage;

    /**
     * @constructor
     */
    public function __construct() {

     	//echo "call in base";
     	//$this->perpage =  Config::get('app.perpage');
    }


}