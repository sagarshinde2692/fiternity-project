<?php


/** 
 * ModelName : Service.
 * Maintains a list of functions used for Service.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class Service extends \Basemodel{

	protected $collection = "services";

	public static $rules = array(
		'name' => 'required',
		'servicecategory_id' => 'required',
		'servicesubcategory_id' => 'required'

		);

	public function category(){

		return $this->belongsTo('Servicecategory','servicecategory_id');
	}		

	public function subcategory(){

		return $this->belongsTo('Servicecategory','servicesubcategory_id');
	}

	public function finder(){
		
		return $this->belongsTo('Finder');
	}

	public function trainer(){

		return $this->belongsTo('Servicetrainer','trainer_id');
	}	

}