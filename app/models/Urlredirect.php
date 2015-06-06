<?php

/** 
 * ModelName : Urlredirect.
 * Maintains a list of functions used for Urlredirect.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Urlredirect extends \Moloquent {

	protected $collection = "urlredirects";

	// Add your validation rules here
	public static $rules = array(
		'name' => 'required'
		//'detail_rating' => 'required|array',
		);

	protected $guarded = array();
	
	

}