<?php

class Offer extends \Basemodel {

	public static $rules = [
		'city_id' => 'required|numeric'
	];

	public function setCityIdAttribute($value){
		$this->attributes['city_id'] = intval($value);
	}

	public function setOrderingAttribute($value){
		$this->attributes['ordering'] = intval($value);
	}

	public function city(){
		return $this->belongsTo('City');
	}

}