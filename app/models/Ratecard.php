<?php

class Ratecard extends \Basemodel {

	protected $collection = "ratecards";

	public static $rules = array(
		'name' => 'required',
		'duration' => 'required',
		'price' => 'required|numeric',
		'special_price' => 'numeric',
		'location_id' => 'required',
		'findercategory_id' => 'required',
		'interest' => 'required',
		'area' => 'required',
		//'short_description' => 'required',
	);

	public static $withoutAppends = false;

	protected function getArrayableAppends()
	{
		if(self::$withoutAppends){
			return [];
		}
		return parent::getArrayableAppends();
	}

	protected $dates = array('start_date', 'expiry_date');

	public function setOrderAttribute($value){
		$this->attributes['order'] = intval($value);
	}
	
	public function finder(){
		return $this->belongsTo('Finder');
	}

	public function reviews(){
		return $this->hasMany('Finder', 'finder_id');
	}

	public function serviceoffers(){
		return $this->hasMany('Serviceoffer','ratecard_id');
	}

	public function service(){
		return $this->belongsTo('Service');
	}

	public function scopeActive ($query){
		return 	$query->where('flags.free_sp', '!=', true)
		->where(function($query){$query->orWhere('start_date', 'exists', false)->orWhere('start_date', '<', new DateTime());})
						->where(function($query){$query->orWhere('expiry_date', 'exists', false)->orWhere('expiry_date', '>', new DateTime(date("d-m-Y",strtotime('-1 days', time()))));})
						;
	}

}