<?php

/** 
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Saavn extends  \Basemodel {

	protected $collection = "saavns";

	public function setIdAttribute($value){

		$this->attributes['_id'] = $value;
	}

}