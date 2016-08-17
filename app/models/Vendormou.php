<?php

/** 
 * ModelName : Vendormou.
 * Maintains a list of functions used for Vendormou.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class Vendormou extends  \Basemodel {

	protected $connection = 'mongodb2';
	
	protected $collection = "vendormous";

	protected $dates = array('collection_date','execution_start_date','contract_start_date','contract_end_date','created_at_after_15_days','generated_link_date');

	

	public function finder(){
		return $this->belongsTo('Finder','vendor_id');
	}

}