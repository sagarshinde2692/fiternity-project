<?php

/** 
 * ModelName : Ozonetelno.
 * Maintains a list of functions used for Ozonetelno.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Ozonetelmissedcallno extends  \Basemodel {

	protected $collection = "ozonetelmissedcallnos";

	public function finder(){
		return $this->belongsTo('Finder','finder_id');
	}

}	