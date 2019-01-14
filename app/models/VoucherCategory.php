<?php

class VoucherCategory extends \Basemodel {

	protected $collection = "vouchercategories";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
}