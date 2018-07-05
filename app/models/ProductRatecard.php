<?php

class ProductRatecard extends \Basemodel {

	protected $collection = "productratecards";
	protected $connection = "mongodb2";

	
	public function product() {
		return $this->belongsTo('Product','product_id');
	}
	
}