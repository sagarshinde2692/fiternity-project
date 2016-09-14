<?php

/** 
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Findercommercial extends  \Basemodel {

	protected $collection = "findercommercials";

	public function finder(){
		return $this->belongsTo('Finder','finder_id');
	}

}