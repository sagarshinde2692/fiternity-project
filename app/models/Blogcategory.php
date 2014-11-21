<?php

/** 
 * ModelName : Blogcategory.
 * Maintains a list of functions used for Blogcategory.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


#use Jenssegers\Mongodb\Eloquent\SoftDeletingTrait;

class Blogcategory extends \Basemodel {

	#use SoftDeletingTrait; 

	protected $collection = "blogcategories";

	#protected $dates = ['deleted_at'];

	public static $rules = array(
		'name' => 'required'
	);

	public function blogs(){
		
		return $this->hasMany('Blog','category_id');
	}


}