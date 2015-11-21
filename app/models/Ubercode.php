<?php

class Ubercode extends \Basemodel {

	protected $collection = "uber";

	public static $rules = array(
		'code' => 'required'
		);
}