<?php

/** 
 * ModelName : Ozonetelno.
 * Maintains a list of functions used for Ozonetelno.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Ozonetelno extends  \Basemodel {

	protected $collection = "ozonetelnos";

	public function finder(){
		return $this->belongsTo('Finder','finder_id');
	}

}	