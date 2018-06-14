<?php

class ProductCategory extends \Basemodel {

	protected $collection = "productcategories";
	protected $connection = "mongodb2";
	public static $rules = array(
		'name' => 'required',
		'status'=>'required'
	);

	


}