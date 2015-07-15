<?php

/** 
 * ModelName : Brand.
 * Maintains a list of functions used for Brand.
 *
 * @author Sanjay Sahu <mjmjadhav@gmail.com>
 */


class Brand extends \Basemodel {

	protected $collection = "brands";

	public static $rules = array(
		'name' => 'required|unique:brands',
		'url' => 'unique:brands',
		'finder_id' => 'required',
		'logo' => 'mimes:jpeg,png|image|max:2000',
		'cover_image' => 'mimes:jpeg,png|image|max:2000',
	);

	public static $update_rules = array(
		'name' => 'required',
		'finder_id' => 'required',
		'logo' => 'mimes:jpeg,png|image|max:2000',
		'cover_image' => 'mimes:jpeg,png|image|max:2000',
	);

	
}