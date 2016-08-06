<?php

/** 
 * ModelName : Event.
 * Maintains a list of functions used for Event.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

namespace App\Models;

class Event extends \Basemodel {

	protected $collection = "events";

	// protected $dates = array('start_date','end_date');

	public function scopeActive ($query){
		return $query->where('status','=','1');
	}

}