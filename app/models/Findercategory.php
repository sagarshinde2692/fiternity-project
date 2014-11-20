<?php


/** 
 * ModelName : FinderCategory.
 * Maintains a list of functions used for Category.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Findercategory extends \Moloquent{

	protected $collection = "findercategories";

	// Add your validation rules here
	public static $rules = array(
		'name' => 'required'
		//'detail_rating' => 'required|array',
		);

	protected $guarded = array();
	
	// Don't forget to fill this array
	//protected $fillable = [];

	
	public function finders(){
		
		return $this->hasMany('Finder');
	}


	public function scopeActive ($query){

		return $query->where('status','=','1');
	}




}

