<?php

/** 
 * @author Shahaan
 * 
 */

class Product extends  \Basemodel {

	protected $collection = "products";
	
	protected $connection = "mongodb2";
	
	// Add your validation rules here
	public static $rules = [
	'title' => 'required',
	'slug' => 'required',
// 	'productcategory_id' => 'required',
// 	'servicecategory_id' => 'required'
	];

	public function productcategory1()
	{
// 		$tsd=$this->belongsTo('ProductCategory',"productcategory.primary");
		return $this->hasMany('ProductCategory',"products","productcategory.secondary");
		
// 		return $this->belongsToMany('Findercategorytag', null, 'finders', 'categorytags');
		
// 		return ["productcategory_primary"=>$tsd,"productcategory_secondary"=>$tsd1];
	}
	

	public function servicecategory()
	{
// 		$tsd=$this->belongsTo('Servicecategory',"servicecategory.primary");
		return $tsd1=$this->belongsToMany('Servicecategory',"servicecategory.secondary");
// 		return ["servicecategory_primary"=>$tsd,"servicecategory_secondary"=>$tsd1]; 
	}
}