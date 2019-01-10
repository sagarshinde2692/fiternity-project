<?php

class VoucherCategory extends \Basemodel {

	protected $collection = "vouchercategories_latest";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}