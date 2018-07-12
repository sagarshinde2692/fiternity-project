<?php

class ProductRatecard extends \Basemodel {

	protected $collection = "productratecards";
	protected $connection = "mongodb2";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}
	public function product() {
		return $this->belongsTo('Product','product_id');
	}
	
}