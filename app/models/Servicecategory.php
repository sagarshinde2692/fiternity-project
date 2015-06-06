<?php


/** 
 * ModelName : Servicecategory.
 * Maintains a list of functions used for Servicecategory.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class Servicecategory extends \Basemodel{

	protected $collection = "servicecategories";

	public static $rules = array(
		'name' => 'required'
		);

	public function categoryservices(){

		return $this->hasMany('Service','servicecategory_id');
	}		

	public function subcategoryservices(){
		
		return $this->hasMany('Service','servicesubcategory_id');
	}	


}