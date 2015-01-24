<?php


/** 
 * ModelName : FinderCategory.
 * Maintains a list of functions used for Category.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Findercategory extends \Basemodel{

	protected $collection = "findercategories";

	public static $rules = array(
		'name' => 'required'
		);

	
	public function finders(){
		
		return $this->hasMany('Finder');
	}


	public function cities(){

		return $this->belongsToMany('City', null, 'findercategorys', 'cities');
	}



}

